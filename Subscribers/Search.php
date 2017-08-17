<?php
/**
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ShopwarePlugins\Connect\Subscribers;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Property\Group;
use Shopware\Models\Property\Option;
use Shopware\Models\Property\Value;

class Search implements SubscriberInterface
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var string
     */
    private $pluginPath;

    /**
     * @var \Shopware_Components_Snippet_Manager
     */
    private $snippetManager;

    /**
     * @param ModelManager $modelManager
     * @param $pluginPath
     * @param \Shopware_Components_Snippet_Manager $snippetManager
     */
    public function __construct(
        ModelManager $modelManager,
        $pluginPath,
        \Shopware_Components_Snippet_Manager $snippetManager
    ) {
        $this->modelManager = $modelManager;
        $this->pluginPath = $pluginPath;
        $this->snippetManager = $snippetManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Search' => 'extendBackendPropertySearch',
        ];
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
                $subject->View()->addTemplateDir($this->pluginPath . 'Views/', 'connect');
                $this->snippetManager->addConfigDir($this->pluginPath . 'Snippets/');

                $entity = $request->getParam('entity', null);

                switch ($entity) {
                    case Group::class:
                    case Option::class:
                    case Value::class:
                        $subject->View()->data = $this->markRecordsAsConnect(
                            $subject->View()->data,
                            $entity
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

    /**
     * @param array $data
     * @param string $modelName
     * @return array
     */
    private function markRecordsAsConnect($data, $modelName)
    {
        $result = [];

        foreach ($data as $row) {
            if (!array_key_exists('id', $row)) {
                continue;
            }
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
