<?php
App::uses('CakeFixtureManager', 'TestSuite/Fixture');
App::uses('CakeTestFixture', 'TestSuite/Fixture');
App::uses('ConnectionManager', 'Model');

/**
 * Nodes Fixture Loader shell
 *
 * Example:
 *  ./Console/cake Common.fixture_loader app.events,app.tags,app.categories
 *  ./Console/cake Common.fixture_loader app.events,app.tags,app.categories --datasource production
 *
 * Allow you to load test fixtures into any data source you may have
 * Its nice to have when you need to see your test data in-app, or just
 * need to update your fixtures in a more friendly interface than PHP arrays
 *
 */
class FixtureLoaderShell extends AppShell {

/**
 * The one and only shell action
 *
 * Find the fixtures from command line and optionally a datasource, and import them
 *
 * @return void
 */
	public function main() {
		$CakeFixtureManager = new NodesFixtureManager();
		$CakeFixtureManager->loadAllFixtures($this->params['datasource'], explode(',', $this->args[0]));
	}

/**
 * get the option parser.
 *
 * @return void
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser
			->description('Load test fixtures into any datasource you want')
			->addArgument('fixtures', array(
				'help' => 'A comma separated list of fixtures to use (Format is same as $fixtures property in CakeTest classes',
				'required' => true
			))
			->addOption('datasource', array(
				'help' => 'Datasource to use',
				'default' => 'default'
			));
	}
}

/**
 * Nodes modified FixtureManager
 *
 * Makes loading the fixtures so much easier
 * and since everything is protected in CakeFixtureManager
 * we need our own class to hack around it
 *
 */
class NodesFixtureManager extends CakeFixtureManager {

	/**
	 * Load a list of $fixtures into a $source
	 *
	 * @param string $source The name of your datasource (e.g. default)
	 * @param array $fixtures An array of fixtures - same format as in CakeTest $fixtures
	 * @return void
	 */
	public function loadAllFixtures($source, $fixtures) {
		$this->_initDb($source);
		$this->_loadFixtures($fixtures);

		CakeLog::debug('Begin fixture import');
		CakeLog::debug('');

		$nested = $this->_db->useNestedTransactions;
		$this->_db->useNestedTransactions = false;
		$this->_db->begin();
		foreach ($fixtures as $f) {
			CakeLog::debug(sprintf('Working on %s', $f));
			if (empty($this->_loaded[$f])) {
				CakeLog::notice('-> Can not find it in the loaded array.. weird');
				continue;
			}

			$fixture = $this->_loaded[$f];
			CakeLog::debug(sprintf('-> Found fixture: %s', get_class($fixture)));

			$this->_setupTable($fixture, $this->_db, true);
			CakeLog::debug('-> Created table "OK"');

			if ($fixture->insert($this->_db)) {
				CakeLog::debug('-> Inserted fixture data "OK"');
			} else {
				CakeLog::error('-> Inserted fixture data "ERROR"');
			}
			CakeLog::debug('');
		}
		$this->_db->commit();
		$this->_useNestedTransactions = $nested;
		CakeLog::debug('Done!');
	}

	/**
	 * Overridden so we can change datasource easily
	 *
	 * @param string $source Name of the datasource
	 * @return void
	 */
	protected function _initDb($source = 'default') {
		if ($this->_initialized) {
			return;
		}
		$db = ConnectionManager::getDataSource($source);
		$db->cacheSources = false;
		$this->_db = $db;
		$this->_initialized = true;
	}
}