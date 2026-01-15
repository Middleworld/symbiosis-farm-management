<?php

namespace Drupal\farmos_menu_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for menu API endpoints.
 */
class MenuApiController extends ControllerBase {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * Constructs a MenuApiController object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu link tree service.
   */
  public function __construct(MenuLinkTreeInterface $menu_tree) {
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu.link_tree')
    );
  }

  /**
   * Returns menu items for a given menu.
   *
   * @param string $menu_name
   *   The machine name of the menu.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The menu items as JSON.
   */
  public function getMenu($menu_name) {
    $parameters = new MenuTreeParameters();
    $parameters->setMaxDepth(3); // Limit depth to prevent infinite nesting
    
    $tree = $this->menuTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $this->menuTree->transform($tree, $manipulators);

    $menu_items = [];
    foreach ($tree as $item) {
      if ($item->link->isEnabled()) {
        $menu_items[] = [
          'title' => $item->link->getTitle(),
          'url' => $item->link->getUrlObject()->toString(),
          'weight' => $item->link->getWeight(),
          'expanded' => $item->link->isExpanded(),
          'enabled' => $item->link->isEnabled(),
          'description' => $item->link->getDescription(),
        ];
      }
    }

    return new JsonResponse([
      'menu_name' => $menu_name,
      'items' => $menu_items,
    ]);
  }

}
