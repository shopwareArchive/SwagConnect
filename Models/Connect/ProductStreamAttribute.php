<?php

namespace Shopware\CustomModels\Connect;

use \Doctrine\ORM\Mapping as ORM;
use \Shopware\Components\Model\ModelEntity;

/**
 * Connect specific attributes for shopware Connect product streams
 *
 * @ORM\Table(name="s_plugin_connect_streams")
 * @ORM\Entity(repositoryClass="ProductStreamAttributeRepository")
 */
class ProductStreamAttribute extends ModelEntity
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(name="id", type="integer", nullable=false)
     */
    protected $id;

    /**
     * @var integer $streamId
     *
     * @ORM\Column(name="stream_id", type="integer", nullable=true)
     */
    protected $streamId;

    /**
     * @var string $exportStatus
     *
     * @ORM\Column(name="export_status", type="text", nullable=true)
     */
    protected $exportStatus;


    /**
     * @var string $exportMessage
     *
     * @ORM\Column(name="export_message", type="text", nullable=true)
     */
    protected $exportMessage;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStreamId()
    {
        return $this->streamId;
    }

    /**
     * @param int $streamId
     */
    public function setStreamId($streamId)
    {
        $this->streamId = $streamId;
    }

    /**
     * @return string
     */
    public function getExportStatus()
    {
        return $this->exportStatus;
    }

    /**
     * @param string $exportStatus
     */
    public function setExportStatus($exportStatus)
    {
        $this->exportStatus = $exportStatus;
    }

    /**
     * @return string
     */
    public function getExportMessage()
    {
        return $this->exportMessage;
    }

    /**
     * @param string $exportMessage
     */
    public function setExportMessage($exportMessage)
    {
        $this->exportMessage = $exportMessage;
    }
}