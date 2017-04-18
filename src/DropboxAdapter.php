<?php

namespace Spatie\FlysystemDropbox;


use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Config;

class DropboxAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;

    /** @var \Spatie\FlysystemDropbox\DropboxClient */
    protected $client;

    public function __construct(DropboxClient $client, string $prefix = null)
    {
        $this->client = $client;
        $this->setPathPrefix($prefix);
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, 'add');
    }

    /**
     * Write a new file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        // TODO: Implement writeStream() method.
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents, 'overwrite');
    }

    /**
     * Update a file using a stream.
     *
     * @param string $path
     * @param resource $resource
     * @param Config $config Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        // TODO: Implement updateStream() method.
    }

    /**
     * @param string $path
     * @param string $newPath
     *
     * @return bool
     */
    public function rename($path, $newPath): bool
    {
        $path = $this->applyPathPrefix($path);
        $newPath = $this->applyPathPrefix($newPath);

        try {
            $this->client->move($path, $newPath);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath): bool
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->client->copy($path, $newpath);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        // TODO: Implement deleteDir() method.
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        // TODO: Implement createDir() method.
    }

    /**
     * Set the visibility for a file.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        // TODO: Implement setVisibility() method.
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        // TODO: Implement has() method.
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        // TODO: Implement read() method.
    }

    /**
     * Read a file as a stream.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        // TODO: Implement readStream() method.
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $listing = [];

        $location = $this->applyPathPrefix($directory);

        $result = $this->client->listContents($location, $recursive);

        if (!count($result['entries'])) {
            return [];
        }

        foreach ($result['entries'] as $object) {
            $path = $this->removePathPrefix($object['path_display']);
            $listing[] = $this->normalizeResponse($object, $path);
        }

        return $listing;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        // TODO: Implement getMetadata() method.
    }

    /**
     * Get the size of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        // TODO: Implement getSize() method.
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        throw new LogicException('The Dropbox API v2 does not support mimetypes. Path: ' . $path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        // TODO: Implement getTimestamp() method.
    }

    public function getTemporaryLink(string $path): string
    {
        return $this->client->getTemporaryLink($path);
    }

    public function getThumbnail(string $path, string $format = 'jpeg', string $size = 'w64h64')
    {
        return $this->client->getThumbnail($path, $format, $size);
    }

    public function applyPathPrefix($path): string
    {
        $path = parent::applyPathPrefix($path);

        return '/' . ltrim(rtrim($path, '/'), '/');
    }

    protected function upload($path, $contents, $mode)
    {
        $location = $this->applyPathPrefix($path);

        $result = $this->client->uploadFromString($location, $mode, $contents);

        return $this->normalizeResponse($result);
    }

    protected function normalizeResponse(array $response): array
    {
        $result = ['path' => ltrim($this->removePathPrefix($response['path_display']), '/')];

        if (isset($response['server_modified'])) {
            $result['timestamp'] = strtotime($response['server_modified']);
        }

        if (isset($response['size'])) {
            $result['bytes'] = $response['size'];
        }

        $result['type'] = $response['.tag'] === 'folder' ? 'dir' : 'file';

        return $result;
    }
}