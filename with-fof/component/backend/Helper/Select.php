<?php
/**
 * @package   AkeebaReleaseSystem
 * @copyright Copyright (c)2010-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\ReleaseSystem\Admin\Helper;

use Akeeba\ReleaseSystem\Admin\Model\Environments;
use Akeeba\ReleaseSystem\Admin\Model\UpdateStreams;
use FOF40\Container\Container;
use FOF40\Utils\Collection;
use Joomla\CMS\HTML\HTMLHelper as JHtml;
use Joomla\CMS\Language\LanguageHelper as JLanguageHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die;

abstract class Select
{
	/**
	 * The component container
	 *
	 * @var   Container
	 */
	private static $container;

	/**
	 * Cache of environment IDs to their titles
	 *
	 * @var   array
	 * @since 5.0.0
	 */
	private static $environmentTitles;

	/**
	 * Get the component's container
	 *
	 * @return  Container
	 */
	private static function getContainer(): Container
	{
		if (is_null(self::$container))
		{
			self::$container = Container::getInstance('com_ars');
		}

		return self::$container;
	}

	/**
	 * Creates a generic SELECT element
	 *
	 * @param   array   $list      A list of options generated by JHtml::_('FEFHelp.select.option'), calls
	 * @param   string  $name      The field name
	 * @param   array   $attribs   HTML attributes for the field
	 * @param   mixed   $selected  The pre-selected value
	 * @param   string  $idTag     The HTML id attribute of the field (do NOT add in $attribs)
	 *
	 * @return  string  The HTML for the SELECT field
	 */
	protected static function genericlist(array $list, string $name, array $attribs, $selected, string $idTag)
	{
		if (empty($attribs))
		{
			$attribs = null;
		}
		else
		{
			$temp = '';

			foreach ($attribs as $key => $value)
			{
				$temp .= $key . ' = "' . $value . '"';
			}

			$attribs = $temp;
		}

		return JHtml::_('FEFHelp.select.genericlist', $list, $name, $attribs, 'value', 'text', $selected, $idTag);
	}

	/**
	 * Returns the title of the specified environment ID
	 *
	 * @param int   $id      Environment ID
	 * @param array $attribs Any HTML attributes for the IMG element
	 *
	 * @return  string  The title of the environment
	 */
	public static function environmentTitle(int $id, array $attribs = []): string
	{
		if (is_null(self::$environmentTitles))
		{
			/** @var Environments $environmentsModel */
			$environmentsModel = self::getContainer()->factory->model('Environments')->tmpInstance();
			// We use getItemsArray instead of get to fetch an associative array
			self::$environmentTitles = $environmentsModel
				->get(true)
				->transform(function(Environments $item) {
					return $item->title;
				});
		}

		if (!isset(self::$environmentTitles[$id]))
		{
			return '';
		}

		return self::$environmentTitles[$id];
	}

	/**
	 * Return an options list for all Environments
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function environments(): array
	{
		/** @var Environments $environmentsModel */
		$environmentsModel = self::getContainer()
			->factory->model('Environments')->tmpInstance();
		$options           = $environmentsModel
			->filter_order('title')
			->filter_order_Dir('ASC')
			->get(true)
			->transform(function (Environments $item) {
				return JHtml::_('FEFHelp.select.option', $item->id, $item->title);
			})->toArray();

		array_unshift($options, JHtml::_('FEFHelp.select.option', '', '- ' . Text::_('COM_ARS_ITEM_FIELD_ENVIRONMENT_SELECT') . ' -'));

		return $options;
	}

	/**
	 * Return a grouped options list for all releases (grouped by category) and ordered by category and version
	 * ascending.
	 *
	 * @param   bool      $addDefault        Add default select text?
	 * @param   int|null  $filterByCategory  Category to filter releases by
	 *
	 * @return  array
	 *
	 * @since   5.0.0
	 */
	public static function releases(bool $addDefault = false, ?int $filterByCategory = 0): array
	{
		$container = self::getContainer();
		$db        = $container->db;
		$query     = $db->getQuery(true)
			->select([
				$db->qn('c.title', 'cat'),
				$db->qn('r.id', 'id'),
				$db->qn('r.version'),
			])->from($db->qn('#__ars_releases', 'r'))
			->join('inner', $db->qn('#__ars_categories', 'c') . ' ON (' . $db->qn('c.id') . ' = ' . $db->qn('r.category_id') . ')')
			->where(
			// published = 1 and type != bleedingedge
				'((' . $db->qn('c.published') . ' = 1 AND ' . $db->qn('type') . ' != ' . $db->q('bleedingedge') . ') OR ' . $db->qn('type') . ' = ' . $db->q('normal') . ')'
			);

		if (!is_null($filterByCategory) && ($filterByCategory > 0))
		{
			$query->where($db->qn('c.id') . ' = ' . $filterByCategory, 'AND');
		}

		$releases = $db->setQuery($query)->loadAssocList();

		if (empty($releases))
		{
			return [];
		}

		uasort($releases, function (array $a, array $b) {
			$catCompare = $a['cat'] <=> $b['cat'];

			if ($catCompare !== 0)
			{
				return $catCompare;
			}

			return version_compare($a['version'], $b['version']);
		});

		array_map(function (array $item) use (&$options, &$lastCat) {
			if ($item['cat'] !== $lastCat)
			{
				if ($lastCat !== null)
				{
					$options[] = JHtml::_('FEFHelp.select.option', '</OPTGROUP>');
				}

				$options[] = JHtml::_('FEFHelp.select.option', '<OPTGROUP>', $item['cat']);
				$lastCat   = $item['cat'];
			}

			$options[] = JHtml::_('FEFHelp.select.option', $item['id'], $item['version']);
		}, $releases);

		if ($lastCat !== null)
		{
			$options[] = JHtml::_('FEFHelp.select.option', '</OPTGROUP>');
		}

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', 0, '- ' . Text::_('COM_ARS_COMMON_RELEASE_SELECT_LABEL') . ' -'));
		}

		return $options;
	}

	/**
	 * Return an options list for all categories
	 *
	 * @param bool $addDefault                     Add default select text?
	 * @param bool $excludeBleedingEdgeUnpublished Should I exclude unpublished Bleeding Edge categories? Default: true.
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function categories(bool $addDefault = false, bool $excludeBleedingEdgeUnpublished = true): array
	{
		$container = self::getContainer();
		$db        = $container->db;

		$query = $db->getQuery(true)
			->select([
				$db->qn('title'),
				$db->qn('id'),
			])->from($db->qn('#__ars_categories'));

		if ($excludeBleedingEdgeUnpublished)
		{
			$query->where(
			// published = 1 and type != bleedingedge
				'(' . $db->qn('published') . ' = 1 AND ' . $db->qn('type') . ' = ' . $db->q('bleedingedge') . ')', 'OR'
			)->where(
			// type = normal
				$db->qn('type') . ' = ' . $db->q('normal'), 'OR'
			);
		}

		$cats = $db->setQuery($query)->loadAssocList();

		if (empty($cats))
		{
			return [];
		}

		$options = array_map(function (array $item) {
			return JHtml::_('FEFHelp.select.option', $item['id'], $item['title']);
		}, $cats);

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', '', '- ' . Text::_('COM_ARS_COMMON_CATEGORY_SELECT_LABEL') . ' -'));
		}

		return $options;
	}

	/**
	 * Return an options list for all Joomla client IDs. Used to set up update streams.
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function client_id(): array
	{
		return [
			JHtml::_('FEFHelp.select.option', '', '- ' . Text::_('COM_ARS_UPDATESTREAM_FIELD_CLIENTID_LBL') . ' -'),
			JHtml::_('FEFHelp.select.option', '1', Text::_('COM_ARS_UPDATESTREAM_FIELD_CLIENTID_BACKEND')),
			JHtml::_('FEFHelp.select.option', '0', Text::_('COM_ARS_UPDATESTREAM_FIELD_CLIENTID_FRONTEND')),
		];
	}

	/**
	 * Return an options list with Joomla update types
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function updateTypes(bool $addDefault = false): array
	{
		$options = [
			JHtml::_('FEFHelp.select.option', 'components', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_COMPONENTS')),
			JHtml::_('FEFHelp.select.option', 'libraries', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_LIBRARIES')),
			JHtml::_('FEFHelp.select.option', 'modules', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_MODULES')),
			JHtml::_('FEFHelp.select.option', 'packages', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_PACKAGES')),
			JHtml::_('FEFHelp.select.option', 'plugins', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_PLUGINS')),
			JHtml::_('FEFHelp.select.option', 'templates', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_TEMPLATES')),
			JHtml::_('FEFHelp.select.option', 'files', Text::_('COM_ARS_UPDATESTREAM_UPDATETYPE_FILES')),
		];

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', '', '- ' . Text::_('COM_ARS_UPDATESTREAM_FIELD_TYPE') . ' -'));
		}

		return $options;
	}

	/**
	 * Returns an options list with all Update Streams
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function updateStreams(bool $addDefault = false): array
	{
		/** @var UpdateStreams $streamModel */
		$streamModel = self::getContainer()
			->factory->model('UpdateStreams')->tmpInstance();

		$options = $streamModel
			->filter_order('name')
			->filter_order_Dir('ASC')
			->get(true)
			->transform(function (UpdateStreams $item) {
				return JHtml::_('FEFHelp.select.option', $item->id, $item->name);
			})->toArray();

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', '', '- ' . Text::_('COM_ARS_ITEM_FIELD_UPDATESTREAM_SELECT') . ' -'));
		}

		return $options;
	}

	/**
	 * Returns an options list with all Update Streams
	 *
	 * @param string $client Which part of the site you want a language list for? Default: site
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function languages(string $client = 'site'): array
	{
		if ($client != 'site' && $client != 'administrator')
		{
			$client = 'site';
		}

		$options = (new Collection(JLanguageHelper::createLanguageList(
			null, constant('JPATH_' . strtoupper($client)), true, true
		)))->sort(function ($a, $b) {
			return strcmp($a['value'], $b['value']);
		})->transform(function (array $item) {
			return JHtml::_('FEFHelp.select.option', $item['value'], $item['text']);
		})->toArray();

		array_unshift($options, JHtml::_('FEFHelp.select.option', '*', Text::_('JALL_LANGUAGE')));

		return $options;
	}

	/**
	 * Returns an options list with all category types
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function categoryType(bool $addDefault = false): array
	{
		$options = [
			JHtml::_('FEFHelp.select.option', 'normal', Text::_('COM_ARS_CATEGORIES_TYPE_NORMAL')),
			JHtml::_('FEFHelp.select.option', 'bleedingedge', Text::_('COM_ARS_CATEGORIES_TYPE_BLEEDINGEDGE')),
		];

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', '', '- ' . Text::_('COM_ARS_COMMON_LBL_SELECTCATTYPE') . ' -'));
		}

		return $options;
	}

	/**
	 * Returns an options list with all item types
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function itemType(bool $addDefault = false): array
	{
		$options = [
			JHtml::_('FEFHelp.select.option', 'link', Text::_('COM_ARS_ITEM_FIELD_TYPE_LINK')),
			JHtml::_('FEFHelp.select.option', 'file', Text::_('COM_ARS_ITEM_FIELD_TYPE_FILE')),
		];

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', '', '- ' . Text::_('COM_ARS_ITEM_FIELD_TYPE_SELECT') . ' -'));
		}

		return $options;

	}

	/**
	 * Returns an options list with all item types
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function maturity(bool $addDefault = false): array
	{
		$options = [
			JHtml::_('FEFHelp.select.option', 'alpha', Text::_('COM_ARS_RELEASES_MATURITY_ALPHA')),
			JHtml::_('FEFHelp.select.option', 'beta', Text::_('COM_ARS_RELEASES_MATURITY_BETA')),
			JHtml::_('FEFHelp.select.option', 'rc', Text::_('COM_ARS_RELEASES_MATURITY_RC')),
			JHtml::_('FEFHelp.select.option', 'stable', Text::_('COM_ARS_RELEASES_MATURITY_STABLE')),
		];

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', '', Text::_('COM_ARS_RELEASES_MATURITY_SELECT')));
		}

		return $options;
	}

	/**
	 * Returns an options list with all Joomla access levels.
	 *
	 * @param bool $addDefault Add default select text?
	 *
	 * @return array
	 *
	 * @since  5.0.0
	 */
	public static function accessLevel(bool $addDefault = false): array
	{
		$db    = static::getContainer()->db;
		$query = $db->getQuery(true)
			->select($db->qn('a.id', 'value') . ', ' . $db->qn('a.title', 'text'))
			->from($db->qn('#__viewlevels', 'a'))
			->group($db->qn(['a.id', 'a.title', 'a.ordering']))
			->order($db->qn('a.ordering') . ' ASC')
			->order($db->qn('title') . ' ASC');

		// Get the options.
		$options = $db->setQuery($query)->loadObjectList();

		if ($addDefault)
		{
			array_unshift($options, JHtml::_('FEFHelp.select.option', '', Text::_('COM_ARS_COMMON_SHOW_ALL_LEVELS')));
		}

		return $options;
	}
}
