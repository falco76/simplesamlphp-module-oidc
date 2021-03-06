<?php

/*
 * This file is part of the simplesamlphp-module-oidc.
 *
 * Copyright (C) 2018 by the Spanish Research and Academic Network.
 *
 * This code was developed by Universidad de Córdoba (UCO https://www.uco.es)
 * for the RedIRIS SIR service (SIR: http://www.rediris.es/sir)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SimpleSAML\Modules\OpenIDConnect\Repositories;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use SimpleSAML\Modules\OpenIDConnect\Entity\ClientEntity;

class ClientRepository extends AbstractDatabaseRepository implements ClientRepositoryInterface
{
    const TABLE_NAME = 'oidc_client';

    public function getTableName()
    {
        return $this->database->applyPrefix(self::TABLE_NAME);
    }

    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
    {
        $client = $this->findById($clientIdentifier);

        if (!$client) {
            return null;
        }

        if (false === $client->isEnabled()) {
            return null;
        }

        return $client;
    }

    public function findById($clientIdentifier)
    {
        $stmt = $this->database->read(
            "SELECT * FROM {$this->getTableName()} WHERE id = :id",
            [
                'id' => $clientIdentifier,
            ]
        );

        if (!$rows = $stmt->fetchAll()) {
            return null;
        }

        return ClientEntity::fromState(current($rows));
    }

    public function findAll()
    {
        $stmt = $this->database->read(
            "SELECT * FROM {$this->getTableName()} ORDER BY name ASC"
        );

        $clients = [];

        foreach ($stmt->fetchAll() as $state) {
            $clients[] = ClientEntity::fromState($state);
        }

        return $clients;
    }

    public function add(ClientEntity $client)
    {
        $this->database->write(
              "INSERT INTO {$this->getTableName()} (id, secret, name, description, auth_source, redirect_uri, scopes, is_enabled) VALUES (:id, :secret, :name, :description, :auth_source, :redirect_uri, :scopes, :is_enabled)",
            $client->getState()
        );
    }

    public function delete(ClientEntity $client)
    {
        $this->database->write(
            "DELETE FROM {$this->getTableName()} WHERE id = :id",
            [
                'id' => $client->getIdentifier(),
            ]
        );
    }

    public function update(ClientEntity $client)
    {
        $this->database->write(
            "UPDATE {$this->getTableName()} SET secret = :secret, name = :name, description = :description, auth_source = :auth_source, redirect_uri = :redirect_uri, scopes = :scopes, is_enabled = :is_enabled WHERE id = :id",
            $client->getState()
        );
    }
}
