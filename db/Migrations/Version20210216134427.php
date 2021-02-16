<?php

declare(strict_types=1);

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210216134427 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE "wireguard_interface_ipv4s" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, interface_id INTEGER DEFAULT NULL, address BLOB NOT NULL --(DC2Type:ip)
        , prefix INTEGER NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EA5C8753AB0BE982 ON "wireguard_interface_ipv4s" (interface_id)');
        $this->addSql('CREATE TABLE "wireguard_interface_ipv6s" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, interface_id INTEGER DEFAULT NULL, address BLOB NOT NULL --(DC2Type:ip)
        , prefix INTEGER NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D86AE5D1AB0BE982 ON "wireguard_interface_ipv6s" (interface_id)');
        $this->addSql('CREATE TABLE "wireguard_interfaces" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, private_key VARCHAR(255) NOT NULL, port INTEGER DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_25B210A65E237E06 ON "wireguard_interfaces" (name)');
        $this->addSql('CREATE TABLE "wireguard_peer_addresses" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, peer_id INTEGER DEFAULT NULL, address BLOB NOT NULL --(DC2Type:ip)
        , prefix INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX IDX_AB85CDC120D91DB4 ON "wireguard_peer_addresses" (peer_id)');
        $this->addSql('CREATE TABLE "wireguard_peers" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, interface_id INTEGER DEFAULT NULL, public_key VARCHAR(255) NOT NULL, psk VARCHAR(255) DEFAULT NULL, keepalive INTEGER NOT NULL, endpoint VARCHAR(255) NOT NULL, port INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX IDX_23ACBD91AB0BE982 ON "wireguard_peers" (interface_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE "wireguard_interface_ipv4s"');
        $this->addSql('DROP TABLE "wireguard_interface_ipv6s"');
        $this->addSql('DROP TABLE "wireguard_interfaces"');
        $this->addSql('DROP TABLE "wireguard_peer_addresses"');
        $this->addSql('DROP TABLE "wireguard_peers"');
    }
}
