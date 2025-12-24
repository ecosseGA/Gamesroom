<?php

namespace IdleChatter\Gamesroom;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;
use XF\Db\Schema\Create;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUninstallTrait;
    use StepRunnerUpgradeTrait;

    public function installStep1()
    {
        $sm = $this->schemaManager();
        foreach ($this->getTables() as $tableName => $callback)
        {
            $sm->createTable($tableName, $callback);
        }
    }
    public function installStep2()
    {
        // Insert default categories
        $categories = [
            ['Action', 'Fast-paced action games', 'fa-bolt', 10],
            ['Puzzle', 'Mind-bending puzzles', 'fa-puzzle-piece', 20],
            ['Strategy', 'Strategic thinking games', 'fa-chess', 30],
            ['Sports', 'Sports and racing games', 'fa-futbol', 40],
            ['Arcade', 'Classic arcade games', 'fa-gamepad', 50]
        ];

        foreach ($categories as $cat)
        {
            $this->db()->insert('xf_gamesroom_category', [
                'title' => $cat[0],
                'description' => $cat[1],
                'icon' => $cat[2],
                'display_order' => $cat[3],
                'active' => 1
            ]);
        }
    }
    
    // Upgrade to 2.2.0 - Add featured column
    public function upgrade2002000Step1()
    {
        $this->schemaManager()->alterTable('xf_gamesroom_game', function(Alter $table)
        {
            $table->addColumn('featured', 'tinyint')->setDefault(0)->after('active');
            $table->addKey('featured');
        });
    }

    public function uninstallStep1()
    {
        $sm = $this->schemaManager();
        foreach (array_keys($this->getTables()) as $tableName)
        {
            $sm->dropTable($tableName);
        }
    }

    protected function getTables(): array
    {
        $tables = [];
        
        $tables['xf_gamesroom_category'] = function (Create $table) {
            $table->addColumn('category_id', 'int')->autoIncrement();
            $table->addColumn('title', 'varchar', 100);
            $table->addColumn('description', 'varchar', 255)->setDefault('');
            $table->addColumn('icon', 'varchar', 50)->setDefault('fa-gamepad');
            $table->addColumn('display_order', 'int')->setDefault(0);
            $table->addColumn('active', 'tinyint')->setDefault(1);
            $table->addColumn('game_count', 'int')->setDefault(0);
            $table->addKey(['display_order', 'title']);
            $table->addKey('active');
        };
        
        $tables['xf_gamesroom_game'] = function (Create $table) {
            $table->addColumn('game_id', 'int')->autoIncrement();
            $table->addColumn('category_id', 'int')->setDefault(0);
            $table->addColumn('title', 'varchar', 150);
            $table->addColumn('description', 'varchar', 500)->setDefault('');
            $table->addColumn('embed_url', 'varchar', 500);
            $table->addColumn('thumbnail_url', 'varchar', 500)->setDefault('');
            $table->addColumn('distributor', 'varchar', 50)->setDefault('custom');
            $table->addColumn('width', 'int')->setDefault(800);
            $table->addColumn('height', 'int')->setDefault(600);
            $table->addColumn('display_order', 'int')->setDefault(0);
            $table->addColumn('active', 'tinyint')->setDefault(1);
            $table->addColumn('featured', 'tinyint')->setDefault(0);
            $table->addColumn('play_count', 'int')->setDefault(0);
            $table->addColumn('create_date', 'int');
            $table->addColumn('update_date', 'int');
            $table->addKey('category_id');
            $table->addKey(['display_order', 'title']);
            $table->addKey('active');
            $table->addKey('featured');
            $table->addKey('create_date');
            $table->addKey('play_count');
        };
        
        return $tables;
    }
}
