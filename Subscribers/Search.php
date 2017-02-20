<?php

namespace ShopwarePlugins\Connect\Subscribers;

use Shopware\Components\Model\ModelManager;

/**
 * Class Property
 * @package ShopwarePlugins\Connect\Subscribers
 */
class Search extends BaseSubscriber
{

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(
        ModelManager $modelManager
    ) {
        parent::__construct();
        $this->modelManager = $modelManager;
    }

    public function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatch_Backend_Search' => 'extendBackendPropertySearch',
        );
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     */
    public function extendBackendPropertySearch(\Enlight_Event_EventArgs $args)
    {
        /** @var $subject \Enlight_Controller_Action */
        $subject = $args->getSubject();
        $request = $subject->Request();

        switch ($request->getActionName()) {
            case 'search':
                $this->registerMyTemplateDir();
                $this->registerMySnippets();

                $entity = $request->getParam('entity', null);

                switch ($entity) {
                    case 'Shopware\Models\Property\Group':
                        $subject->View()->data = $this->markRecordsAsConnect(
                            $subject->View()->data,
                            'Shopware\Models\Property\Group'
                        );
                        break;
                    case 'Shopware\Models\Property\Option':
                        $subject->View()->data = $this->markRecordsAsConnect(
                            $subject->View()->data,
                            'Shopware\Models\Property\Option'
                        );
                        break;
                    case 'Shopware\Models\Property\Value':
                        $subject->View()->data = $this->markRecordsAsConnect(
                            $subject->View()->data,
                            'Shopware\Models\Property\Value'
                        );
                        break;
                    default:
                        break;
                }

                break;
            default:
                break;
        }
    }

    public function markRecordsAsConnect($data, $modelName)
    {
        $result = [];

        foreach ($data as $row) {
            $recordId = $row['id'];
            $model = $this->modelManager->getRepository($modelName)->find($recordId);

            $attribute = null;
            if ($model) {
                $attribute = $model->getAttribute();
            }

            if ($attribute && $attribute->getConnectIsRemote()) {
                $row['connect'] = true;
            }

            $result[] = $row;
        }

        return $result;
    }
}