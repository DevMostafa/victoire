<?php

namespace Victoire\Bundle\MediaBundle\Helper\RemoteSlide;

use Victoire\Bundle\MediaBundle\Entity\Media;
use Victoire\Bundle\MediaBundle\Form\RemoteSlide\RemoteSlideType;
use Victoire\Bundle\MediaBundle\Helper\Media\AbstractMediaHandler;

/**
 * RemoteSlideStrategy.
 */
class RemoteSlideHandler extends AbstractMediaHandler
{
    /**
     * @var string
     */
    const CONTENT_TYPE = 'remote/slide';

    const TYPE = 'slide';

    /**
     * @return string
     */
    public function getName()
    {
        return 'Remote Slide Handler';
    }

    /**
     * @return string
     */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * @return RemoteSlideType
     */
    public function getFormType()
    {
        return new RemoteSlideType();
    }

    /**
     * @param mixed $object
     *
     * @return bool
     */
    public function canHandle($object)
    {
        if ((is_string($object)) || ($object instanceof Media && $object->getContentType() == self::CONTENT_TYPE)) {
            return true;
        }

        return false;
    }

    /**
     * @param Media $media
     *
     * @return RemoteSlideHelper
     */
    public function getFormHelper(Media $media)
    {
        return new RemoteSlideHelper($media);
    }

    /**
     * @param Media $media
     *
     * @throws \RuntimeException when the file does not exist
     */
    public function prepareMedia(Media $media)
    {
        if (null == $media->getUuid()) {
            $uuid = uniqid();
            $media->setUuid($uuid);
        }
        $slide = new RemoteSlideHelper($media);
        $code = $slide->getCode();
        //update thumbnail
        switch ($slide->getType()) {
            case 'slideshare':
                try {
                    $json = json_decode(file_get_contents('http://www.slideshare.net/api/oembed/2?url=http://www.slideshare.net/slideshow/embed_code/'.$code.'&format=json'));
                    $slide->setThumbnailUrl('http:'.$json->thumbnail);
                } catch (\ErrorException $e) {
                }
                break;
        }
    }

    /**
     * @param Media $media
     */
    public function saveMedia(Media $media)
    {
    }

    /**
     * @param Media $media
     */
    public function removeMedia(Media $media)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function updateMedia(Media $media)
    {
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function getAddUrlFor(array $params = [])
    {
        return [
                'slide' => [
                        'path'   => 'VictoireMediaBundle_folder_slidecreate',
                        'params' => [
                                'folderId' => $params['folderId'],
                        ],
                ],
        ];
    }

    /**
     * @param mixed $data
     *
     * @return Media
     */
    public function createNew($data)
    {
        $result = null;
        if (is_string($data)) {
            if (strpos($data, 'http') !== 0) {
                $data = 'http://'.$data;
            }
            $parsedUrl = parse_url($data);
            switch ($parsedUrl['host']) {
                case 'www.slideshare.net':
                case 'slideshare.net':
                    $result = new Media();
                    $slide = new RemoteSlideHelper($result);
                    $slide->setType('slideshare');
                    $json = json_decode(file_get_contents('http://www.slideshare.net/api/oembed/2?url='.$data.'&format=json'));
                    $slide->setCode($json->{'slideshow_id'});
                    $result = $slide->getMedia();
                    $result->setName('SlideShare '.$data);
                    break;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getShowTemplate(Media $media)
    {
        return 'VictoireMediaBundle:Media\RemoteSlide:show.html.twig';
    }

    /**
     * @param Media  $media    The media entity
     * @param string $basepath The base path
     *
     * @return string
     */
    public function getImageUrl(Media $media, $basepath)
    {
        $helper = new RemoteSlideHelper($media);

        return $helper->getThumbnailUrl();
    }

    /**
     * @return array
     */
    public function getAddFolderActions()
    {
        return [
                self::TYPE => [
                    'type' => self::TYPE,
                    'name' => 'media.slide.add', ],
                ];
    }
}
