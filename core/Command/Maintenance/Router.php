<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Core\Command\Maintenance;

use OC\AppFramework\App;
use OC\Core\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Router extends Base {

	protected function configure() {
		parent::configure();

		$this
			->setName('maintenance:router')
			->setDescription('prints all routes');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		// load all routes
		\OC::$server->getRouter()->loadRoutes();
		// get the collections
		$collections = \OC::$server->getRouter()->getAllRoutes();
		$items = [];
		foreach ($collections as $c) {
			$new = $c->all();
			$items = $items + $new;
		}
		ksort($items);
		foreach ($items as $name => $route) {
			$controller = $this->buildController($name);
			$items[$name] = array_merge([
				'methods' => $route->getMethods(),
				'path' => $route->getPath(),
				'requirements' => $route->getRequirements(),
			], $controller);
		}
		$this->writeArrayInOutputFormat($input, $output, $items);
	}

	private function buildController($name) {
		$parts = explode('.', $name);
		$appName = $parts[0];
		$controllerName = $parts[1];
		$method = $parts[2];
		$appNameSpace = App::buildAppNamespace($appName);
		if (!class_exists($controllerName)) {
			$controllerName = $appNameSpace . '\\Controller\\' . $controllerName;
		}
		if (!class_exists($controllerName)) {
			return [];
		}
		$reflection = new \ReflectionMethod($controllerName, $method);
		$docs = $reflection->getDocComment();

		// extract everything prefixed by @ and first letter uppercase
		preg_match_all('/@([A-Z]\w+)/', $docs, $matches);
		$annotations = $matches[1];

		return [
			'annotations' => $annotations,
			'controllerClass' => $controllerName
		];
	}

}
