<?php

namespace Drupal\firebase\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\help\HelpSectionManager;

/**
 * Controller routines for help routes.
 */
class FirebaseHelpController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The help section plugin manager.
   *
   * @var \Drupal\help\HelpSectionManager
   */
  protected $helpManager;

  /**
   * Provides a list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Creates a new HelpController.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\help\HelpSectionManager $help_manager
   *   The help section manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_list
   *   Provides a list of available modules.
   */
  public function __construct(RouteMatchInterface $route_match, HelpSectionManager $help_manager, ModuleExtensionList $module_list) {
    $this->routeMatch = $route_match;
    $this->helpManager = $help_manager;
    $this->moduleExtensionList = $module_list;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('plugin.manager.help_section'),
      $container->get('extension.list.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function index() {
    $build = [];
    $name = 'firebase';

    $module_name = $this->moduleHandler()->getName($name);
    $build['#title'] = 'Firebase Help';
    $temp = $this->moduleHandler()->invoke($name, 'help', [
      "help.page.$name", $this->routeMatch,
    ]);

    if (!is_array($temp)) {
      $temp = ['#markup' => $temp];
    }
    $build['top'] = $temp;

    // Only print list of administration pages if the module in question has
    // any such pages associated with it.
    $admin_tasks = system_get_module_admin_tasks($name, $this->moduleExtensionList->getExtensionInfo($name));
    if (!empty($admin_tasks)) {
      $links = [];
      foreach ($admin_tasks as $task) {
        $link['url'] = $task['url'];
        $link['title'] = $task['title'];
        $links[] = $link;
      }
      $build['links'] = [
        '#theme' => 'links__help',
        '#heading' => [
          'level' => 'h3',
          'text' => $this->t('@module administration pages', ['@module' => $module_name]),
        ],
        '#links' => $links,
      ];
    }
    return $build;
  }

}
