<?php
/**
 * Items Model for a Workflow Component.
 *
 * @package     Joomla.Administrator
 * @subpackage  com_workflow
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @since       4.0.0
 */
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

HTMLHelper::_('behavior.tooltip');
HTMLHelper::_('behavior.multiselect');

$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));

$saveOrder = $listOrder == 'w.ordering';

$orderingColumn = 'created';
$saveOrderingUrl = '';

if (strpos($listOrder, 'modified') !== false)
{
	$orderingColumn = 'modified';
}

if ($saveOrder)
{
	$saveOrderingUrl = 'index.php?option=com_workflow&task=workflows.saveOrderAjax&tmpl=component&extension=' . $this->escape($this->extension) . '&' . Session::getFormToken() . '=1';
	HTMLHelper::_('draggablelist.draggable');
}

$extension = $this->escape($this->state->get('filter.extension'));

$user = Factory::getUser();
$userId = $user->id;
?>
<form action="<?php echo Route::_('index.php?option=com_workflow&view=workflows&extension=' . $extension); ?>" method="post" name="adminForm" id="adminForm">
	<div class="row">
		<?php if (!empty($this->sidebar)) : ?>
            <div id="j-sidebar-container" class="col-md-2">
				<?php echo $this->sidebar; ?>
            </div>
		<?php endif; ?>
        <div class="<?php if (!empty($this->sidebar)) {echo 'col-md-10'; } else { echo 'col-md-12'; } ?>">
			<div id="j-main-container" class="j-main-container">
				<?php
					// Search tools bar
					echo LayoutHelper::render('joomla.searchtools.default', array('view' => $this));
				?>
				<?php if (empty($this->workflows)) : ?>
					<div class="alert alert-warning">
						<?php echo Text::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
					</div>
				<?php else: ?>
					<table class="table">
						<caption id="captionTable" class="sr-only">
							<?php echo Text::_('COM_WORKFLOW_WORKFLOWS_TABLE_CAPTION'); ?>, <?php echo Text::_('JGLOBAL_SORTED_BY'); ?>
						</caption>
						<thead>
							<tr>
								<th scope="col" style="width:1%" class="text-center d-none d-md-table-cell">
									<?php echo HTMLHelper::_('searchtools.sort', '', 'w.ordering', $listDirn, $listOrder, null, 'asc', 'JGRID_HEADING_ORDERING', 'icon-menu-2'); ?>
								</th>
								<td style="width:1%" class="text-center hidden-sm-down">
									<?php echo HTMLHelper::_('grid.checkall'); ?>
								</td>
								<th scope="col"  style="width:1%" class="text-center hidden-sm-down">
									<?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'w.published', $listDirn, $listOrder); ?>
								</th>
								<th class="hidden-sm-down">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_WORKFLOW_NAME', 'w.title', $listDirn, $listOrder); ?>
								</th>
								<th scope="col" style="width:10%" class="text-center hidden-sm-down">
									<?php echo Text::_('COM_WORKFLOW_DEFAULT'); ?>
								</th>
								<th scope="col" style="width:10%" class="text-center hidden-sm-down">
									<?php echo Text::_('COM_WORKFLOW_COUNT_STAGES'); ?>
								</th>
								<th scope="col" style="width:10%" class="text-center hidden-sm-down">
									<?php echo Text::_('COM_WORKFLOW_COUNT_TRANSITIONS'); ?>
								</th>
								<th scope="col" style="width:10%" class="text-right hidden-sm-down">
									<?php echo HTMLHelper::_('searchtools.sort', 'COM_WORKFLOW_ID', 'w.id', $listDirn, $listOrder); ?>
								</th>
							</tr>
						</thead>
						<tbody <?php if ($saveOrder) :?> class="js-draggable" data-url="<?php echo $saveOrderingUrl; ?>" data-direction="<?php echo strtolower($listDirn); ?>" data-nested="false"<?php endif; ?>>
						<?php foreach ($this->workflows as $i => $item):
							$states = Route::_('index.php?option=com_workflow&view=stages&workflow_id=' . $item->id . '&extension=' . $extension);
							$transitions = Route::_('index.php?option=com_workflow&view=transitions&workflow_id=' . $item->id . '&extension=' . $extension);
							$edit = Route::_('index.php?option=com_workflow&task=workflow.edit&id=' . $item->id . '&extension=' . $extension);

							$isCore     = !empty($item->core);
							$canEdit    = $user->authorise('core.edit', $extension . '.workflow.' . $item->id);
							// @TODO set proper checkin fields
							$canCheckin = true || $user->authorise('core.admin', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
							$canEditOwn = $user->authorise('core.edit.own',   $extension . '.workflow.' . $item->id) && $item->created_by == $userId;
							$canChange  = $user->authorise('core.edit.state', $extension . '.workflow.' . $item->id) && $canCheckin;
							?>
							<tr class="row<?php echo $i % 2; ?>" data-dragable-group="0">
								<td class="order text-center hidden-sm-down">
									<?php
									$iconClass = '';
									if (!$canChange)
									{
										$iconClass = ' inactive';
									}
									elseif (!$saveOrder)
									{
										$iconClass = ' inactive tip-top hasTooltip" title="' . HTMLHelper::_('tooltipText', 'JORDERINGDISABLED');
									}
									?>
									<span class="sortable-handler<?php echo $iconClass ?>">
										<span class="icon-menu" aria-hidden="true"></span>
									</span>
									<?php if ($canChange && $saveOrder) : ?>
										<input type="text" style="display:none" name="order[]" size="5" value="<?php echo $item->ordering; ?>" class="width-20 text-area-order">
									<?php endif; ?>
								</td>
								<td class="order text-center hidden-sm-down">
									<?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
								</td>
								<td class="text-center">
									<div class="btn-group">
										<?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'workflows.', $canChange && !$isCore); ?>
									</div>
								</td>
								<th scope="row">
									<?php if (!$isCore && ($canEdit || $canEditOwn)) : ?>
										<?php $editIcon = '<span class="fa fa-pen-square mr-2" aria-hidden="true"></span>'; ?>
										<a href="<?php echo $edit; ?>" title="<?php echo Text::_('JACTION_EDIT', true); ?> <?php echo Text::_($item->title, true); ?>">
											<?php echo $editIcon; ?><?php echo $this->escape(Text::_($item->title)); ?>
										</a>
										<div class="small"><?php echo $item->description; ?></div>
									<?php else: ?>
										<?php echo $this->escape(Text::_($item->title)); ?>
										<div class="small"><?php echo $item->description; ?></div>
									<?php endif; ?>
								</th>
								<td class="text-center hidden-sm-down">
									<?php echo HTMLHelper::_('jgrid.isdefault', $item->default, $i, 'workflows.', $canChange); ?>
								</td>
								<td class="text-center btns hidden-sm-down">
									<a class="badge <?php echo ($item->count_states > 0) ? 'badge-warning' : 'badge-secondary'; ?>" title="<?php echo Text::_('COM_WORKFLOW_COUNT_STAGES', true); ?>" href="<?php echo Route::_('index.php?option=com_workflow&view=stages&workflow_id=' . (int) $item->id . '&extension=' . $extension); ?>">
										<?php echo $item->count_states; ?></a>
								</td>
								<td class="text-center btns hidden-sm-down">
									<a class="badge <?php echo ($item->count_transitions > 0) ? 'badge-info' : 'badge-secondary'; ?>" title="<?php echo Text::_('COM_WORKFLOW_COUNT_TRANSITIONS', true); ?>" href="<?php echo Route::_('index.php?option=com_workflow&view=transitions&workflow_id=' . (int) $item->id . '&extension=' . $extension); ?>">
										<?php echo $item->count_transitions; ?></a>
								</td>
								<td class="text-right">
									<?php echo $item->id; ?>
								</td>
							</tr>
						<?php endforeach ?>
					</table>
					<?php // load the pagination. ?>
					<?php echo $this->pagination->getListFooter(); ?>

				<?php endif; ?>
				<input type="hidden" name="task" value="">
				<input type="hidden" name="boxchecked" value="0">
				<input type="hidden" name="extension" value="<?php echo $extension ?>">
				<?php echo HTMLHelper::_('form.token'); ?>
			</div>
		</div>
	</div>
</form>
