<?php

namespace Drupal\event_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Event Block.
 *
 * @Block(
 *   id = "event_block",
 *   admin_label = @Translation("Event Block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", label = @Translation("Node"))
 *   }
 * )
 */
class EventBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a new EventBlock.
   *
   * @param array<string, mixed> $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Creates a new instance of the EventBlock plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container interface.
   * @param array<string, mixed> $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return self
   *   The new instance of the EventBlock plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the content of the EventBlock.
   *
   * @return array<string, mixed>
   *   An array containing the built content of the EventBlock.
   */
  public function build(): array {
    // Get the current node from the context.
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof Node && $node->bundle() === 'event') {
      $type = $node->get('field_event_type')->entity->id();
      if (is_string($type)) {
        $related_events = $this->getRelatedEvents($node, $type);

        return [
          '#theme' => 'item_list',
          '#items' => $related_events,
          '#title' => $this->t('Related Events'),
        ];
      }
    }

    return [];
  }

  /**
   * Fetches related events based on type and date.
   *
   * @param \Drupal\node\Entity\Node $current_node
   *   The current node.
   * @param string $type
   *   The event type.
   *
   * @return array<int, string>
   *   An array of related event links.
   */
  private function getRelatedEvents(Node $current_node, string $type): array {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->condition('field_event_type', $type)
      ->condition('field_date_range', date('Y-m-d'), '>=')
      ->condition('nid', $current_node->id(), '!=')
      ->sort('field_date_range', 'ASC')
      ->range(0, 3)
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (count($nids) < 3) {
      $additional_query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'event')
        ->condition('status', 1)
        ->condition('field_event_type', $type, '!=')
        ->condition('field_date_range', date('Y-m-d'), '>=')
        ->sort('field_date_range', 'ASC')
        ->range(0, 3 - count($nids))
        ->accessCheck(TRUE);

      $additional_nids = $additional_query->execute();
      $nids = array_merge($nids, $additional_nids);
    }

    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $related_events = [];
    foreach ($nodes as $node) {
      $related_events[] = $node->toLink()->toString();
    }

    return $related_events;
  }

}
