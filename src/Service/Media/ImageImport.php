<?php

declare(strict_types=1);

namespace MothershipSimpleApi\Service\Media;

use Exception;
use Shopware\Core\Content\Media\Exception\MediaNotFoundException;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Helper-Server, um Bilder anhand einer URL runterzuladen.
 *
 * Implementierung basiert teilweise auf:
 *
 * @link https://shopwarian.com/how-to-add-images-to-products-programmatically-in-shopware-6/
 */
class ImageImport
{
    public const TEMP_NAME = 'image-import-from-url';      //prefix for temporary files, downloaded from URL
    public const MEDIA_FOLDER = 'product';
    protected ?string $mediaFolderId = null;

    public function __construct(
        protected EntityRepository $mediaRepository,
        protected EntityRepository $mediaFolderRepository,
        protected MediaService $mediaService,
        protected FileSaver $fileSaver
    ) {
    }

    /**
     * Method, that downloads a file from a URL and returns an ID of a newly created media, based on it
     *
     * @param string      $resource
     * @param Context     $context
     * @param string|null $customFileName     Der Dateiname kann im Payload explizit übergeben werden.
     * @param string|null $mediaFolderName
     *
     * @return string
     */
    public function addImageToMediaFromResource(string $resource, Context $context, string $mediaFolderName = null, string|null $customFileName = null): string
    {
        if (null === $mediaFolderName) {
            $this->mediaFolderId = $this->getMediaDefaultFolderId(self::MEDIA_FOLDER, $context);
        } else {
            $this->mediaFolderId = $this->getMediaFolderIdByName($mediaFolderName, $context);
        }

        $mediaId = null;

        //get the file name and extension
        if ($customFileName) {
            $fileNameParts = explode('.', $customFileName);
        } else {
            //parse the URL
            $filePathParts = explode('/', $resource);
            $fileNameParts = explode('.', array_pop($filePathParts));
        }
        $fileName = $fileNameParts[0];
        $fileExtension = end($fileNameParts);


        if ($fileName && $fileExtension) {
            //copy the file from the URL to the newly created local temporary file
            $filePath = tempnam(sys_get_temp_dir(), self::TEMP_NAME);
            file_put_contents($filePath, file_get_contents($resource));

            //create media record from the image
            $mediaId = $this->upsertMediaFromFile($filePath, $fileName, $fileExtension, $context);
        }

        return $mediaId;
    }

    protected function getMediaFolderIdByName(string $mediaFolderName, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('media_folder.name', $mediaFolderName));
        $criteria->setLimit(1);
        $defaultFolder = $this->mediaFolderRepository->search($criteria, $context);
        $defaultFolderId = null;
        if ($defaultFolder->count() === 1) {
            $defaultFolderId = $defaultFolder->first()?->getId();
        }

        return $defaultFolderId;
    }

    /**
     * Aus dem Media-Service übernommen. Lädt die Ordner-ID aus der Tabelle 'media_folder'. Es ist aktuell
     * nicht geplant, dass ein anderes Verzeichnis angegeben wird.
     *
     * @param string  $folder
     * @param Context $context
     *
     * @return string|null
     */
    protected function getMediaDefaultFolderId(string $folder, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('media_folder.defaultFolder.entity', $folder));
        $criteria->addAssociation('defaultFolder');
        $criteria->setLimit(1);
        $defaultFolder = $this->mediaFolderRepository->search($criteria, $context);
        $defaultFolderId = null;
        if ($defaultFolder->count() === 1) {
            $defaultFolderId = $defaultFolder->first()?->getId();
        }

        return $defaultFolderId;
    }

    /**
     * Erstellt bzw. aktualisiert Bilder sowohl in der Tabelle 'media' als auch im lokalen Dateisystem.
     *
     * @param string  $filePath
     * @param string  $fileName
     * @param string  $fileExtension
     * @param Context $context
     *
     * @return string|null
     */
    private function upsertMediaFromFile(string $filePath, string $fileName, string $fileExtension, Context $context): ?string
    {
        //get additional info on the file
        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath);
        $hash     = hash_file('sha1', $filePath);

        // Die Medien-ID ist immer unique
        $mediaId = $this->getMediaId($fileName, $fileExtension, $context);

        // Prüfen, ob es das Bild überhaupt gibt
        $mediaEntity = $this->getMediaEntityById($mediaId, $context);
        $mediaFile   = new MediaFile($filePath, $mimeType, $fileExtension, $fileSize, $hash);

        if (null === $mediaEntity) {
            // Es existiert kein Eintrag in der Tabelle 'media'. Ein neuer Eintrag sollte also angelegt werden
            try {
                $this->mediaService->loadFile($mediaId, $context);
            } catch (MediaNotFoundException) {
                // Noch keine Behandlung
                try {
                    $this->createMedia($mediaId, $context);
                    $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
                } catch (Exception $e) {
                    echo($e->getMessage());
                    // Noch keine Behandlung
                }
            }
        } else if ($mediaEntity->getMetaData()['hash'] !== $hash) {
            $this->fileSaver->persistFileToMedia($mediaFile, $fileName, $mediaId, $context);
        }

        return $mediaId;
    }

    /**
     * Erstellt eine eindeutige UUID basierend auf dem Dateinamen und der Endung.
     *
     * @param string $fileName
     * @param string $fileExtension
     *
     * @return string
     */
    private function getMediaId(string $fileName, string $fileExtension, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $fileName));
        $criteria->addFilter(new EqualsFilter('fileExtension', $fileExtension));
        $mediaEntity =  $this->mediaRepository->search($criteria, $context)->first();

        if (null !== $mediaEntity) {
            return $mediaEntity->getId();
        }
        // Die Medien-ID ist immer unique
        return Uuid::fromStringToHex($fileName . $fileExtension);
    }

    protected function getMediaEntityById(string $mediaId, Context $context): MediaEntity|null
    {
        $criteria = new Criteria();
        $criteria->setIds([strtolower($mediaId)]);
        return $this->mediaRepository->search($criteria, $context)->first();
    }

    private function createMedia(string $mediaId, Context $context): void
    {
        $this->mediaRepository->create(
            [
                [
                    'id'            => $mediaId,
                    'private'       => false,
                    'mediaFolderId' => $this->mediaFolderId,
                ],
            ],
            $context
        );
    }
}
