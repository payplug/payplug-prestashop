<?php

namespace PayPlug\tests\traits;

trait DataBaseProvider
{
    protected $pdo;

    /**
     * @param string $entityClass
     *
     * @throws Exception
     */
    public function insertData($entityClass, array $data)
    {
        if (!class_exists($entityClass) || !property_exists($entityClass, 'definition')) {
            throw new Exception('Invalid entity: ' . $entityClass);
        }

        $definition = $entityClass->getDefinition();
        $tableName = $definition['table'];
        $fields = array_keys($definition['fields']);

        $columns = implode(', ', $fields);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $sql = 'INSERT INTO ' . $tableName . ' (' . $columns . ') VALUES (' . $placeholders . ')';
        $stmt = $this->pdo->prepare($sql);

        $values = [];
        foreach ($fields as $field) {
            $values[] = isset($data[$field]) ? $data[$field] : null;
        }

        $stmt->execute($values);
    }

    /**
     * @param string $entityClass
     * @param int $id
     *
     * @throws Exception
     */
    public function updateData($entityClass, $id, array $data)
    {
        if (!class_exists($entityClass) || !property_exists($entityClass, 'definition')) {
            throw new Exception('Invalid entity: ' . $entityClass);
        }

        $definition = $entityClass->getDefinition();
        $tableName = $definition['table'];
        $primaryKey = $definition['primary'];

        $updates = [];
        $values = [];

        foreach ($data as $field => $value) {
            $updates[] = $field . ' = ?';
            $values[] = $value;
        }

        $values[] = $id; // Ajout de l'ID pour la clause WHERE

        $sql = 'UPDATE ' . $tableName . ' SET ' . implode(', ', ' . $updates . ') . ' WHERE ' . $primaryKey . ' = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
    }

    /**
     * @param string $entityClass
     * @param int $id
     *
     * @throws Exception
     */
    public function deleteData($entityClass, $id)
    {
        if (!class_exists($entityClass) || !property_exists($entityClass, 'definition')) {
            throw new Exception('Invalid entity: ' . $entityClass);
        }

        $definition = $entityClass->getDefinition();
        $tableName = $definition['table'];
        $primaryKey = $definition['primary'];

        $sql = 'DELETE FROM ' . $tableName . ' WHERE ' . $primaryKey . ' = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * @param string $entityClass
     * @param string $field
     * @param mixed $value
     *
     * @throws Exception
     *
     * @return array|null
     */
    public function getData($entityClass, $field, $value)
    {
        if (!class_exists($entityClass) || !property_exists($entityClass, 'definition')) {
            throw new Exception('Invalid entity: ' . $entityClass);
        }

        $definition = $entityClass::$definition;
        $tableName = $definition['table'];

        // Vérifier que le champ existe bien dans la définition de l'entité
        if ($field !== $definition['primary'] && !array_key_exists($field, $definition['fields'])) {
            throw new Exception('Unknow field: ' . $field . ' in table ' . $tableName);
        }

        $stmt = $this->pdo->prepare('SELECT * FROM ' . $tableName . ' WHERE ' . $field . ' = ?');
        $stmt->execute([$value]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    // Exemple usage
    public function testGetDataByField()
    {
        // Insérer une carte
        $this->insertData(CardEntity::class, [
            'id_customer' => 1,
            'id_company' => 2,
            'is_sandbox' => 1,
            'id_card' => 'card_12345',
            'last4' => '4242',
            'exp_month' => '12',
            'exp_year' => '2030',
            'brand' => 'Visa',
            'country' => 'FR',
            'metadata' => null,
        ]);

        // Récupérer par id_card au lieu de l'ID
        $card = $this->getData(CardEntity::class, 'id_card', 'card_12345');

        // Vérifier les valeurs
        $this->assertNotNull($card);
        $this->assertEquals('Visa', $card['brand']);
        $this->assertEquals('4242', $card['last4']);
    }

    /**
     * @throws Exception
     */
    protected function setUpDatabase(array $entities)
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        foreach ($entities as $entity) {
            if (!class_exists($entity) || !property_exists($entity, 'definition')) {
                throw new Exception('Invalid entity: ' . $entity);
            }

            $definition = $entity::$definition;
            $this->createTable($definition);
        }
    }

    private function createTable(array $definition)
    {
        $tableName = $definition['table'];
        $primaryKey = $definition['primary'];
        $fields = $definition['fields'];

        $sql = 'CREATE TABLE ' . $tableName . ' (';
        $columns = [];

        // Ajouter la clé primaire
        $columns[] = $primaryKey . ' INTEGER PRIMARY KEY AUTOINCREMENT';

        // Construire les colonnes selon la définition
        foreach ($fields as $fieldName => $fieldProps) {
            $type = $this->convertType($fieldProps['type']);
            $required = !empty($fieldProps['required']) ? 'NOT NULL' : '';
            $columns[] = $fieldName . ' ' . $type . ' ' . $required;
        }

        $sql .= implode(', ', $columns) . ')';
        $this->pdo->exec($sql);
    }

    /**
     * @param string $type
     *
     * @throws Exception
     *
     * @return string
     */
    private function convertType($type)
    {
        switch ($type) {
            case 'integer':
                return 'INTEGER';
            case 'boolean':
                return 'INTEGER';
            case 'string':
                return 'TEXT';
            default:
                throw new Exception('Invalid type given: ' . $type);
        }
    }
}
