<?php
/**
 * Created by PhpStorm.
 * User: Dalibor Stojaković
 * Date: 06.02.17.
 * Time: 11:06
 */
namespace Drupal\drupal8_custom\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * @SearchApiProcessor(
 *   id = "bookmark_processor",
 *   label = @Translation("Bookmark indexing"),
 *   description = @Translation("Indexing followers of owner"),
 *   stages = {
 *     "add_properties" = 1,
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -30
 *   }
 * )
 */
class BookmarkProcessor extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   *
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = array();

    if (!$datasource) {
      // Ensure that our fields are defined.
      $fields['followers'] = array(
        'label' => $this->t('Followers'),
        'description' => $this->t('Followers from Owner'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      );

      foreach ($fields as $field_id => $field_definition) {
        $properties[$field_id] = new ProcessorProperty($field_definition);
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
        $entity = $item->getOriginalObject()->getValue();
        $owner = $entity->getOwner();
        $users = \Drupal::entityQuery('flagging')
          ->condition('entity_type','user')
          ->condition('entity_id',$owner->id())
          ->condition('flag_id','follow')
          ->execute();
        $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL,'followers');
        foreach ($fields as $field) {
          foreach($users as $user){
            $field->addValue($user);
          }
        }
    }
  }
  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
      $this->ensureField(NULL, 'followers', 'integer');
  }

}
