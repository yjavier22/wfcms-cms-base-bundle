<?php

namespace Wf\Bundle\CmsBaseBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\Provider\MenuProviderInterface;

class MenuProvider implements MenuProviderInterface
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * Retrieves a menu by its name
     *
     * @param string $name = slug of root element
     * @param array $options
     * @return \Knp\Menu\ItemInterface
     * @throws \InvalidArgumentException if the menu does not exists
     */
    public function get($name, array $options = array())
    {
        if ($this->has($name)) {

            $factory = $this->container->get('knp_menu.factory');
            $repository = $this->container->get('wf_cms.repository.menu');

            $menu = $factory->createItem($name);

            if ($menu === null) {
                throw new \InvalidArgumentException(sprintf('The menu "%s" is not defined.', $name));
            }

            $items = $repository->getMenuItems($name);
            foreach ($items as $item) {
                $item = $this->setItemData($item);

                $menuItem = $factory->createFromArray($item);
                if (isset($item['current']) && $item['current'] == true) {
                    $menuItem->setCurrent(true);
                }

                $menu->addChild($menuItem);
            }

            return $menu;
        }
    }

    /**
     *  Item must have same format as returned by MenuItem->toArray()
     *  Available keys:
     *      'name'
     *      'label'
     *      'uri'
     *      'attributes'
     *      'labelAttributes'
     *      'linkAttributes'
     *      'childrenAttributes'
     *      'extras'
     *      'display'
     *      'displayChildren'
     *      'children' => array(...)
     */
    public function setItemData($item)
    {
        $currentUrl = $this->container->get('request')->getRequestUri();
        $details = $item['details'];

        $item['name'] = $item['title'];
        $item['attributes']['class'] = $item['cssClass'];
        $item['extras']['type'] = $item['type'];
        $item['extras']['details'] = $details;

        switch ($item['type']) {
            case 'url':
                $item['uri'] = $details['url'];

                if (isset($details['url-external']) && $details['url-external']) {
                    $item['linkAttributes'] = array('target' => '_blank');
                }

                break;
            case 'category':
                $attributes = array(
                    'categorySlug' => $details['category'],
                );
                $item['uri'] = $this->container->get('router')->generate('wf_category_show', $attributes);
                break;
        }

        // set current
        if(isset($item['uri']) && $item['uri'] == $currentUrl) {
            $item['current'] = true;
        }

        foreach ($item['__children'] as $child) {
            $item['children'][] = $this->setItemData($child);
        }

        return $item;
    }

    /**
     * Checks whether a menu exists in this provider
     *
     * @param string $name
     * @param array $options
     * @return bool
     */
    public function has($name, array $options = array())
    {
        $repository = $this->container->get('wf_cms.repository.menu');
        $menu = $repository->findOneBySlug($name);

        return $menu !== null;
    }
}