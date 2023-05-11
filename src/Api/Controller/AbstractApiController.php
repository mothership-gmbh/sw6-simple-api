<?php declare(strict_types=1);

namespace MothershipSimpleApi\Api\Controller;

use Shopware\Core\Framework\Api\Acl\AclCriteriaValidator;
use Shopware\Core\Framework\Api\Acl\Role\AclRoleDefinition;
use Shopware\Core\Framework\Api\Controller\ApiController;
use Shopware\Core\Framework\Api\Converter\ApiVersionConverter;
use Shopware\Core\Framework\Api\Exception\MissingPrivilegeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityProtection\EntityProtectionValidator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityProtection\ReadProtection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\DefinitionNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\MissingReverseAssociation;
use Shopware\Core\Framework\DataAbstractionLayer\Field\AssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Search\CompositeEntitySearcher;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\RequestCriteriaBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Serializer;

/**
 * Dieser API-Controller basiert auf der Implementierung in Shopware. Wir machen das, damit wir bei den
 * JSON-Filter-Kriterien zu 100% mit Shopware kompatibel sind.
 *
 * Viele relevante Methoden im API-Controller von Shopware sind private, daher mussten wir sie in diese Klasse
 * kopieren um sie in unserer eigenen API verwenden zu können.
 *
 * @see \Shopware\Core\Framework\Api\Controller\ApiController
 *
 * @Route(defaults={"_routeScope"={"api"}})
 */
class AbstractApiController extends ApiController
{

    /**
     * @var DefinitionInstanceRegistry
     */
    protected $definitionRegistry;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var RequestCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * @var CompositeEntitySearcher
     */
    protected $compositeEntitySearcher;

    /**
     * @var ApiVersionConverter
     */
    protected $apiVersionConverter;

    /**
     * @var EntityProtectionValidator
     */
    protected $entityProtectionValidator;

    /**
     * @var AclCriteriaValidator
     */
    protected $criteriaValidator;

    /**
     * @internal
     */
    public function __construct(
        DefinitionInstanceRegistry $definitionRegistry,
        Serializer                 $serializer,
        RequestCriteriaBuilder     $criteriaBuilder,
        CompositeEntitySearcher    $compositeEntitySearcher,
        ApiVersionConverter        $apiVersionConverter,
        EntityProtectionValidator  $entityProtectionValidator,
        AclCriteriaValidator       $criteriaValidator
    )
    {
        parent::__construct(
            $definitionRegistry,
            $serializer,
            $criteriaBuilder,
            $compositeEntitySearcher,
            $apiVersionConverter,
            $entityProtectionValidator,
            $criteriaValidator
        );
        $this->definitionRegistry = $definitionRegistry;
        $this->serializer = $serializer;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->compositeEntitySearcher = $compositeEntitySearcher;
        $this->apiVersionConverter = $apiVersionConverter;
        $this->entityProtectionValidator = $entityProtectionValidator;
        $this->criteriaValidator = $criteriaValidator;
    }

    protected function resolveSearch(Request $request, Context $context, string $entityName, string $path): array
    {
        $pathSegments = $this->buildEntityPath($entityName, $path, $context);
        $permissions = $this->validatePathSegments($context, $pathSegments, AclRoleDefinition::PRIVILEGE_READ);

        $first = array_shift($pathSegments);

        /** @var EntityDefinition|string $definition */
        $definition = $first['definition'];

        if (!$definition) {
            throw new NotFoundHttpException('The requested entity does not exist.');
        }

        $repository = $this->definitionRegistry->getRepository($definition->getEntityName());

        $criteria = new Criteria();
        if (empty($pathSegments)) {
            $criteria = $this->criteriaBuilder->handleRequest($request, $criteria, $definition, $context);

            // trigger acl validation
            $nested = $this->criteriaValidator->validate($definition->getEntityName(), $criteria, $context);
            $permissions = array_unique(array_filter(array_merge($permissions, $nested)));

            if (!empty($permissions)) {
                throw new MissingPrivilegeException($permissions);
            }

            return [$criteria, $repository];
        }

        $child = array_pop($pathSegments);
        $parent = $first;

        if (!empty($pathSegments)) {
            $parent = array_pop($pathSegments);
        }

        $association = $child['field'];

        $parentDefinition = $parent['definition'];

        $definition = $child['definition'];
        if ($association instanceof ManyToManyAssociationField) {
            $definition = $association->getToManyReferenceDefinition();
        }

        $criteria = $this->criteriaBuilder->handleRequest($request, $criteria, $definition, $context);

        if ($association instanceof ManyToManyAssociationField) {
            //fetch inverse association definition for filter
            $reverse = $definition->getFields()->filter(
                function (Field $field) use ($association) {
                    return $field instanceof ManyToManyAssociationField && $association->getMappingDefinition() === $field->getMappingDefinition();
                }
            );

            //contains now the inverse side association: category.products
            $reverse = $reverse->first();
            if (!$reverse) {
                throw new MissingReverseAssociation($definition->getEntityName(), $parentDefinition);
            }

            $criteria->addFilter(
                new EqualsFilter(
                    sprintf('%s.%s.id', $definition->getEntityName(), $reverse->getPropertyName()),
                    $parent['value']
                )
            );

            /** @var EntityDefinition $parentDefinition */
            if ($parentDefinition->isVersionAware()) {
                $criteria->addFilter(
                    new EqualsFilter(
                        sprintf('%s.%s.versionId', $definition->getEntityName(), $reverse->getPropertyName()),
                        $context->getVersionId()
                    )
                );
            }
        } elseif ($association instanceof OneToManyAssociationField) {
            /*
             * Example
             * Route:           /api/product/SW1/prices
             * $definition:     \Shopware\Core\Content\Product\Definition\ProductPriceDefinition
             */

            //get foreign key definition of reference
            $foreignKey = $definition->getFields()->getByStorageName(
                $association->getReferenceField()
            );

            $criteria->addFilter(
                new EqualsFilter(
                //add filter to parent value: prices.productId = SW1
                    $definition->getEntityName() . '.' . $foreignKey->getPropertyName(),
                    $parent['value']
                )
            );
        } elseif ($association instanceof ManyToOneAssociationField) {
            /*
             * Example
             * Route:           /api/product/SW1/manufacturer
             * $definition:     \Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerDefinition
             */

            //get inverse association to filter to parent value
            $reverse = $definition->getFields()->filter(
                function (Field $field) use ($parentDefinition) {
                    return $field instanceof AssociationField && $parentDefinition === $field->getReferenceDefinition();
                }
            );
            $reverse = $reverse->first();
            if (!$reverse) {
                throw new MissingReverseAssociation($definition->getEntityName(), $parentDefinition);
            }

            $criteria->addFilter(
                new EqualsFilter(
                //filter inverse association to parent value:  manufacturer.products.id = SW1
                    sprintf('%s.%s.id', $definition->getEntityName(), $reverse->getPropertyName()),
                    $parent['value']
                )
            );
        } elseif ($association instanceof OneToOneAssociationField) {
            /*
             * Example
             * Route:           /api/order/xxxx/orderCustomer
             * $definition:     \Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerDefinition
             */

            //get inverse association to filter to parent value
            $reverse = $definition->getFields()->filter(
                function (Field $field) use ($parentDefinition) {
                    return $field instanceof OneToOneAssociationField && $parentDefinition === $field->getReferenceDefinition();
                }
            );
            $reverse = $reverse->first();
            if (!$reverse) {
                throw new MissingReverseAssociation($definition->getEntityName(), $parentDefinition);
            }

            $criteria->addFilter(
                new EqualsFilter(
                //filter inverse association to parent value:  order_customer.order_id = xxxx
                    sprintf('%s.%s.id', $definition->getEntityName(), $reverse->getPropertyName()),
                    $parent['value']
                )
            );
        }

        $repository = $this->definitionRegistry->getRepository($definition->getEntityName());

        $nested = $this->criteriaValidator->validate($definition->getEntityName(), $criteria, $context);
        $permissions = array_unique(array_filter(array_merge($permissions, $nested)));

        if (!empty($permissions)) {
            throw new MissingPrivilegeException($permissions);
        }

        return [$criteria, $repository];
    }

    protected function validatePathSegments(Context $context, array $pathSegments, string $privilege): array
    {
        $child = array_pop($pathSegments);

        $missing = [];

        foreach ($pathSegments as $segment) {
            // you need detail privileges for every parent entity
            $missing[] = $this->validateAclPermissions(
                $context,
                $this->getDefinitionForPathSegment($segment),
                AclRoleDefinition::PRIVILEGE_READ
            );
        }

        $missing[] = $this->validateAclPermissions($context, $this->getDefinitionForPathSegment($child), $privilege);

        return array_unique(array_filter($missing));
    }

    protected function getDefinitionForPathSegment(array $segment): EntityDefinition
    {
        $definition = $segment['definition'];

        if ($segment['field'] instanceof ManyToManyAssociationField) {
            $definition = $segment['field']->getToManyReferenceDefinition();
        }

        return $definition;
    }


    protected function validateAclPermissions(Context $context, EntityDefinition $entity, string $privilege): ?string
    {
        $resource = $entity->getEntityName();

        if ($entity instanceof EntityTranslationDefinition) {
            $resource = $entity->getParentDefinition()->getEntityName();
        }

        if (!$context->isAllowed($resource . ':' . $privilege)) {
            return $resource . ':' . $privilege;
        }

        return null;
    }

    protected function buildEntityPath(
        string  $entityName,
        string  $pathInfo,
        Context $context,
        array   $protections = [ReadProtection::class]
    ): array
    {
        $pathInfo = str_replace('/extensions/', '/', $pathInfo);
        $exploded = explode('/', $entityName . '/' . ltrim($pathInfo, '/'));

        $parts = [];
        foreach ($exploded as $index => $part) {
            if ($index % 2) {
                continue;
            }

            if (empty($part)) {
                continue;
            }

            $value = $exploded[$index + 1] ?? null;

            if (empty($parts)) {
                $part = $this->urlToSnakeCase($part);
            } else {
                $part = $this->urlToCamelCase($part);
            }

            $parts[] = [
                'entity' => $part,
                'value'  => $value,
            ];
        }

        /** @var array{'entity': string, 'value': string|null} $first */
        $first = array_shift($parts);

        try {
            $root = $this->definitionRegistry->getByEntityName($first['entity']);
        } catch (DefinitionNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage(), $e);
        }

        $entities = [
            [
                'entity'     => $first['entity'],
                'value'      => $first['value'],
                'definition' => $root,
                'field'      => null,
            ],
        ];

        foreach ($parts as $part) {
            /** @var AssociationField|null $field */
            $field = $root->getFields()->get($part['entity']);
            if (!$field) {
                $path = implode('.', array_column($entities, 'entity')) . '.' . $part['entity'];

                throw new NotFoundHttpException(sprintf('Resource at path "%s" is not an existing relation.', $path));
            }

            if ($field instanceof ManyToManyAssociationField) {
                $root = $field->getToManyReferenceDefinition();
            } else {
                $root = $field->getReferenceDefinition();
            }

            $entities[] = [
                'entity'     => $part['entity'],
                'value'      => $part['value'],
                'definition' => $field->getReferenceDefinition(),
                'field'      => $field,
            ];
        }

        $context->scope(Context::CRUD_API_SCOPE, function (Context $context) use ($entities, $protections): void {
            $this->entityProtectionValidator->validateEntityPath($entities, $protections, $context);
        });

        return $entities;
    }

    protected function urlToSnakeCase(string $name): string
    {
        return str_replace('-', '_', $name);
    }

    protected function urlToCamelCase(string $name): string
    {
        $parts = explode('-', $name);
        $parts = array_map('ucfirst', $parts);

        return lcfirst(implode('', $parts));
    }

    protected function getDefinitionOfPath(string $entityName, string $path, Context $context): EntityDefinition
    {
        $pathSegments = $this->buildEntityPath($entityName, $path, $context);

        $first = array_shift($pathSegments);

        /** @var EntityDefinition|string $definition */
        $definition = $first['definition'];

        if (empty($pathSegments)) {
            return $definition;
        }

        $child = array_pop($pathSegments);

        $association = $child['field'];

        if ($association instanceof ManyToManyAssociationField) {
            /*
             * Example:
             * route:           /api/product/SW1/categories
             * $definition:     \Shopware\Core\Content\Category\CategoryDefinition
             */
            return $association->getToManyReferenceDefinition();
        }

        return $child['definition'];
    }

}
