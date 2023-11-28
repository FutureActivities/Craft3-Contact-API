<?php
namespace futureactivities\contactapi\services;

use Craft;
use yii\base\Component;
use futureactivities\contactapi\Plugin;
use craft\elements\Asset;
use craft\web\UploadedFile;
use craft\helpers\Assets as AssetsHelper;

class Assets extends Component
{
    /**
     * Save an asset
     * 
     * @param UploadedFile $uploadedFile
     * @param int $folderId
     * @return Asset
     * @throws BadRequestHttpException
     * @throws UploadFailedException
     */
    public function uploadNewAsset(UploadedFile $uploadedFile, $folderId) 
    {
        if (empty($folderId))
            throw new \Exception('No target destination provided for uploading');
    
        if ($uploadedFile === null)
            throw new \Exception('No file was uploaded');
    
        $assets = Craft::$app->getAssets();
    
        if ($uploadedFile->getHasError())
            throw new \Exception($uploadedFile->error);
    
        // Move the uploaded file to the temp folder
        if (($tempPath = $uploadedFile->saveAsTempFile()) === false)
            throw new \Exception('Unable to write to destination folder');
    
        if (empty($folderId))
            throw new \Exception('The target destination provided for uploading is not valid');
    
        $folder = $assets->findFolder(['id' => $folderId]);
    
        if (!$folder)
            throw new \Exception('The target folder provided for uploading is not valid');
    
        // Check the permissions to upload in the resolved folder.
        $filename = AssetsHelper::prepareAssetName($uploadedFile->name);
    
        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->filename = $filename;
        $asset->newFolderId = $folder->id;
        $asset->volumeId = $folder->volumeId;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);
    
        $result = Craft::$app->getElements()->saveElement($asset);
    
        return $asset;
    }
    
    /**
     * Get the selected volume / path folder id
     */
    public function resolveVolumePath(string $uploadSource, string $subpath) 
    {
        $volumeId = $this->_volumeIdBySourceKey($this->_folderSourceToVolumeSource($uploadSource));
        $assetsService = Craft::$app->getAssets();

        if ($volumeId === null || ($rootFolder = $assetsService->getRootFolderByVolumeId($volumeId)) === null) {
            throw new \Exception('Unable to resolve volume');
        }

        // Are we looking for a subfolder?
        $subpath = is_string($subpath) ? trim($subpath, '/') : '';

        if ($subpath === '') {
            $folderId = $rootFolder->id;
        } else {
            $folder = $assetsService->findFolder([
                'volumeId' => $volumeId,
                'path' => $subpath . '/'
            ]);

            // Ensure that the folder exists
            if (!$folder) {
                $volume = Craft::$app->getVolumes()->getVolumeById($volumeId);
                $folderId = $assetsService->ensureFolderByFullPathAndVolume($subpath, $volume);
            } else {
                $folderId = $folder->id;
            }
        }
        
        return $folderId;   
    }
    
    /**
     * Convert a folder source to a volume source
     */
    protected function _folderSourceToVolumeSource($sourceKey): string
    {
        if ($sourceKey && is_string($sourceKey) && strpos($sourceKey, 'folder:') === 0) {
            $parts = explode(':', $sourceKey);
            $folder = Craft::$app->getAssets()->getFolderByUid($parts[1]);

            if ($folder) {
                try {
                    /** @var Volume $volume */
                    $volume = $folder->getVolume();
                    return 'volume:' . $volume->uid;
                } catch (InvalidConfigException $e) {
                    // The volume is probably soft-deleted. Just pretend the folder didn't exist.
                }
            }
        }

        return (string)$sourceKey;
    }
    
    /**
     * Get the volume id from the volume source
     */
    protected function _volumeIdBySourceKey(string $sourceKey)
    {
        $parts = explode(':', $sourceKey, 2);

        if (count($parts) !== 2) {
            return null;
        }

        /** @var Volume|null $volume */
        $volume = Craft::$app->getVolumes()->getVolumeByUid($parts[1]);

        return $volume ? $volume->id : null;
    }
}