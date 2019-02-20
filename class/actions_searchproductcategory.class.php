<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    class/actions_searchproductcategory.class.php
 * \ingroup searchproductcategory
 * \brief   This file is an example hook overload class file
 *          Put some comments here
 */

/**
 * Class ActionsSearchProductCategory
 */
class ActionsSearchProductCategory
{
	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var array Errors
	 */
	public $errors = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    &$object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          &$action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function formAddObjectLine ($parameters, &$object, &$action, $hookmanager) {
		
		global $db,$langs,$user,$conf,$inputalsopricewithtax;

		$TContext = explode(':',$parameters['context']);

        if (in_array('propalcard',$TContext) || in_array('ordercard',$TContext) || in_array('invoicecard',$TContext)
            || in_array('supplier_proposalcard',$TContext) || in_array('ordersuppliercard',$TContext) || in_array('invoicesuppliercard',$TContext))
        {
            ?>
            <script type="text/javascript">
                var spc_object_type = '<?php echo $object->element ?>';
                var spc_object_id = '<?php echo $object->id ?>';
                var spc_fk_soc = '<?php echo $object->socid; ?>';
            </script>
            <?php
			if(empty($conf->global->SPC_USE_ONLY_POPIN) && $user->rights->searchproductcategory->user->search) {
        	//Charger les liste des projets de type feuille de temps pas encore facturé
				$colspan1 = 4;
				$colspan2 = 4;
				if (!empty($inputalsopricewithtax)) { $colspan1++; $colspan2++; }
				if (!empty($conf->global->PRODUCT_USE_UNITS)) $colspan1++;
				if (!empty($conf->margin->enabled)) 
				{
					$colspan1++;
					if ($user->rights->margins->creer && ! empty($conf->global->DISPLAY_MARGIN_RATES)) $colspan1++;
					if ($user->rights->margins->creer && ! empty($conf->global->DISPLAY_MARK_RATES)) $colspan1++;
				}
				
	        	$langs->load('searchproductcategory@searchproductcategory');
				?>

				<tr class="liste_titre nodrag nodrop">
					<td colspan="<?php echo $colspan1; ?>"><?php echo $langs->trans('SearchByCategory') ?></td>
					<td align="right"><?php echo $langs->trans('Qty'); ?></td>
					<td align="center" colspan="<?php echo $colspan2; ?>">&nbsp;<?php if (!empty($conf->global->SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE)) { echo $langs->trans('subtotal_title_to_add_under_title'); } ?></td>
				</tr>
				<tr class="pair">
					<td colspan="<?php echo $colspan1; ?>">
						<div id="arboresenceCategoryProduct" spc-role="arbo-multiple">
							
						</div>
					</td>
					<td class="nobottom" align="right">
						<input id="qty_spc" type="text" value="1" size="5" class="flat" />
					</td>
					<td valign="middle" align="center" colspan="<?php echo $colspan2; ?>">
						<?php if (!empty($conf->global->SUBTOTAL_ALLOW_ADD_LINE_UNDER_TITLE)) {
							dol_include_once('/subtotal/class/subtotal.class.php');
							$TTitle = TSubtotal::getAllTitleFromDocument($object);
							echo getHtmlSelectTitle($object);
						} ?>
						<input id="addline_spc" class="button" type="button" name="addline_timesheet" value="<?php echo $langs->trans('Add') ?>">
					</td>
				</tr>
				
				<?php				
			}

        }

		return 0;
	}
	
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		$TContext = explode(':', $parameters['context']);
		
		if (in_array('nomenclaturecard', $TContext))
		{
			if (GETPOST('json')) echo '<script type="text/javascript" src="'. dol_buildpath('/searchproductcategory/js/searchproductcategory.js.php', 1).'" ></script>' ;
		}
	}
}