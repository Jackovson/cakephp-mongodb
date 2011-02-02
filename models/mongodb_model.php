<?php

/**
 * Classe abstraite pour les model mongo
 *
 * @package Models
 * @author Jackovson
 * 
 */
abstract class MongodbModel extends AppModel{

	public $__associationKeys = array(
		'belongsTo' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'counterCache'),
		'hasOne' => array('className', 'foreignKey','conditions', 'fields','order', 'dependent'),
		'hasMany' => array('className', 'foreignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'dependent', 'exclusive', 'finderQuery', 'counterQuery'),
		'hasAndBelongsToMany' => array('className', 'joinTable', 'with', 'foreignKey', 'associationForeignKey', 'conditions', 'fields', 'order', 'limit', 'offset', 'unique', 'finderQuery', 'deleteQuery', 'insertQuery'),
		'hasList' => array('className', 'listName', 'conditions', 'fields', 'order', 'limit', 'offset', 'unique', 'finderQuery', 'deleteQuery', 'insertQuery')
	);
	
	public $__associations = array('belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany', 'hasList');

	public $primaryKey = '_id';

	public $actsAs = array(
		'Mongodb.SqlCompatible',
	);


	/**
	 * Build an array-based association from string.
	 *
	 * @param string $type 'belongsTo', 'hasOne', 'hasMany', 'hasAndBelongsToMany'
	 * @return void
	 * @access private
	 */
	function __generateAssociation($type) {
		foreach ($this->{$type} as $assocKey => $assocData) {
			$class = $assocKey;
			$dynamicWith = false;

			foreach ($this->__associationKeys[$type] as $key) {

				if (!isset($this->{$type}[$assocKey][$key]) || $this->{$type}[$assocKey][$key] === null) {
					$data = '';

					switch ($key) {
						case 'fields':
							$data = '';
						break;

						case 'foreignKey':
							$data = (($type == 'belongsTo') ? Inflector::underscore($assocKey) : Inflector::singularize($this->table)) . '_id';
						break;

						case 'associationForeignKey':
							$data = Inflector::singularize($this->{$class}->table) . '_id';
						break;

						case 'with':
							$data = Inflector::camelize(Inflector::singularize($this->{$type}[$assocKey]['joinTable']));
							$dynamicWith = true;
						break;

						case 'joinTable':
							$tables = array($this->table, $this->{$class}->table);
							sort ($tables);
							$data = $tables[0] . '_' . $tables[1];
						break;

						case 'className':
							$data = $class;
						break;

						case 'unique':
							$data = true;
						break;

						case 'listName':
							$data = Inflector::singularize($this->{$class}->table) . '_ids';
						break;
					}
					$this->{$type}[$assocKey][$key] = $data;
				}
			}

			if (!empty($this->{$type}[$assocKey]['with'])) {
				$joinClass = $this->{$type}[$assocKey]['with'];
				if (is_array($joinClass)) {
					$joinClass = key($joinClass);
				}

				$plugin = null;
				if (strpos($joinClass, '.') !== false) {
					list($plugin, $joinClass) = explode('.', $joinClass);
					$plugin .= '.';
					$this->{$type}[$assocKey]['with'] = $joinClass;
				}

				if (!ClassRegistry::isKeySet($joinClass) && $dynamicWith === true) {
					$this->{$joinClass} = new AppModel(array(
						'name' => $joinClass,
						'table' => $this->{$type}[$assocKey]['joinTable'],
						'ds' => $this->useDbConfig
					));
				} else {
					$this->__constructLinkedModel($joinClass, $plugin . $joinClass);
					$this->{$type}[$assocKey]['joinTable'] = $this->{$joinClass}->table;
				}

				if (count($this->{$joinClass}->schema()) <= 2 && $this->{$joinClass}->primaryKey !== false) {
					$this->{$joinClass}->primaryKey = $this->{$type}[$assocKey]['foreignKey'];
				}
			}
		}
	}
}
