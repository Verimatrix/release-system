<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Component\ARS\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;

class ArsItemsField extends ArsReleasesField
{
	protected $type = 'ArsItems';

	protected function getInput()
	{
		$catId       = null;
		$catStateKey = $this->element['cat_state_key'];

		if ($catStateKey)
		{
			/** @var CMSApplication $app */
			$app   = Factory::getApplication();
			$catId = $app->getUserState($catStateKey);
		}

		$relId       = null;
		$relStateKey = $this->element['rel_state_key'];

		if ($relStateKey)
		{
			/** @var CMSApplication $app */
			$app   = Factory::getApplication();
			$relId = $app->getUserState($relStateKey);
		}


		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select([
				$db->qn('i.id'),
				$db->qn('i.title'),
				$db->qn('r.version'),
				$db->qn('c.title', 'cat_title'),
			])
			->from($db->qn('#__ars_items', 'i'))
			->join('LEFT', $db->quoteName('#__ars_releases', 'r'),
				$db->quoteName('r.id') . ' = ' . $db->quoteName('i.release_id')
			)
			->join('LEFT', $db->quoteName('#__ars_categories', 'c'),
				$db->quoteName('c.id') . ' = ' . $db->quoteName('r.category_id')
			);

		if ($relId)
		{
			$query
				->where($db->quoteName('i.release_id') . ' = :relid')
				->bind(':relid', $relId);
		}

		if ($catId)
		{
			$query
				->where($db->quoteName('r.category_id') . ' = :catid')
				->bind(':catid', $catId);
		}

		$objectList = $db->setQuery($query)->loadObjectList() ?? [];

		if ($relId)
		{
			// Filtered by release: flat list of items in this release
			foreach ($objectList as $o)
			{
				$this->addOption($o->title, [
					'value' => $o->id,
				]);
			}
		}
		elseif ($catId)
		{
			// Filtered by category only: list grouped by version, e.g. "1.0" -> "Item"
			$temp = [];

			foreach ($objectList as $o)
			{
				$key          = $o->version;
				$temp[$key]   = $temp[$key] ?? [];
				$temp[$key][] = $o;
			}

			foreach ($temp as $label => $options)
			{
				$this->addGroup($label, $options);
			}
		}
		else
		{
			// Unfiltered: list grouped by category + release e.g. "Foobar 1.0" -> "Item"
			$temp = [];

			foreach ($objectList as $o)
			{
				$key          = $o->cat_title . ' ' . $o->version;
				$temp[$key]   = $temp[$key] ?? [];
				$temp[$key][] = $o;
			}

			foreach ($temp as $label => $options)
			{
				$this->addGroup($label, $options);
			}
		}

		return parent::getInput();
	}
}