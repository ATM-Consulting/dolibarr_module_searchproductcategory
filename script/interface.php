<?php


	require '../config.php';
	dol_include_once('/categories/class/categorie.class.php');
	dol_include_once('/product/class/product.class.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/commande/class/commande.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	dol_include_once('/supplier_proposal/class/supplier_proposal.class.php');
	dol_include_once('/fourn/class/fournisseur.commande.class.php');
	dol_include_once('/fourn/class/fournisseur.facture.class.php');

	$get=GETPOST('get');
	$put=GETPOST('put');

	switch ($get) {
		case 'categories':
			$fk_parent = (int)GETPOST('fk_parent');
			$keyword= GETPOST('keyword');
			$productKeyword = GETPOST('productKeyword');
			$fk_soc = GETPOST('fk_soc');
			$is_supplier = GETPOST('is_supplier', 'int');

			$Tab =array(
				'TCategory'=>_categories($fk_parent, $keyword)
				,'TProduct'=>_products($fk_parent, $is_supplier, $productKeyword)
			);

			if (!empty($conf->global->PRODUIT_MULTIPRICES))
			{
				require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
				$societe = new Societe($db);
				$societe->fetch($fk_soc);
				
				$Tab['default_price_level'] = 1;
				if ($societe->id > 0)
				{
					$Tab['default_price_level'] = $societe->price_level;
				}
			}
			
			__out($Tab,'json');
					
			break;
	}
	
	switch ($put) {
		case 'addline':
			
			$object_type=GETPOST('object_type');
			$object_id=(int)GETPOST('object_id');
			$qty=(float)GETPOST('qty');
			$TProduct=GETPOST('TProduct');
			$TProductPrice=GETPOST('TProductPrice');
			$txtva=(float)GETPOST('txtva');
			if($object_type == 'supplier_proposal') $object_type = 'SupplierProposal';
			if($object_type == 'order_supplier') $object_type = 'CommandeFournisseur';
			if($object_type == 'invoice_supplier') $object_type = 'FactureFournisseur';

			if(!empty($TProduct)) {
				$o=new $object_type($db);
				//$o=new Propal($db);
				$o->fetch($object_id);
				
				if(empty($o->thirdparty) && method_exists($o, 'fetch_thirdparty')) {
					$o->fetch_thirdparty();
				}

				foreach($TProduct as $fk_product) {
					$p=new Product($db);
					$p->fetch($fk_product);

					$txtva = get_default_tva($mysoc, $o->thirdparty, $p->id);

					$price = 0;
					if (!empty($conf->global->PRODUIT_MULTIPRICES) && !empty($TProductPrice[$fk_product])) {
						$price = price2num($TProductPrice[$fk_product]);

						if (isset($p->multiprices_tva_tx[$o->thirdparty->price_level])) $txtva=$p->multiprices_tva_tx[$o->thirdparty->price_level];
					} elseif(!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
	                                        require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

        	                                $prodcustprice = new Productcustomerprice($db);

                	                        $filter = array('t.fk_product' => $p->id, 't.fk_soc' => $o->socid);

                        	                $result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
                                	        if ($result) {
	                                                // If there is some prices specific to the customer
	                                                if (count($prodcustprice->lines) > 0) {
	                                                        $price = price($prodcustprice->lines[0]->price);
	                                                        $txtva = ($prodcustprice->lines[0]->default_vat_code ? $prodcustprice->lines[0]->tva_tx . ' ('.$prodcustprice->lines[0]->default_vat_code.' )' : $prodcustprice->lines[0]->tva_tx);
	                                                        if ($prodcustprice->lines[0]->default_vat_code && ! preg_match('/\(.*\)/', $tva_tx)) $txtva.= ' ('.$prodcustprice->lines[0]->default_vat_code.')';
	                                                }
	                                        }
					}
					if (empty($price)) $price = $p->price;
					
					
					$remise_percent=0;
					$info_bits=0;
					$fk_remise_except=0;
					$price_base_type='HT';
					$pu_ttc=0;
					$date_start='';
					$date_end='';
					$type=0;
					$rang=-1;
					$special_code=0;
					$fk_parent_line=0;
					$fk_fournprice=null;
					$pa_ht=0;
					$label='';
					$array_options=0;
					$fk_unit=$p->fk_unit;
					$origin='';
					$origin_id=0;
					$pu_ht_devise = 0;
					$ventil = 0;
					$situation_percent = 100;
					$fk_prev_id = 0;
					
					if($o->element == 'commande')
					{
					    $res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product, $remise_percent, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $date_start, $date_end, $type, $rang, $special_code, $fk_parent_line, $fk_fournprice, $pa_ht, $label,$array_options, $fk_unit, $origin, $origin_id, $pu_ht_devise);
					}
					elseif($o->element == 'propal')
					{
						$res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product, $remise_percent, $price_base_type, $pu_ttc, $info_bits, $type, $rang, $special_code, $fk_parent_line, $fk_fournprice, $pa_ht, $label,$date_start, $date_end,$array_options, $fk_unit, $origin, $origin_id, $pu_ht_devise, $fk_remise_except);
					}
					elseif($o->element == 'facture')
					{
						$res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product, $remise_percent, $date_start, $date_end, $ventil, $info_bits, $fk_remise_except, $price_base_type, $pu_ttc, $type, $rang, $special_code, $origin, $origin_id, $fk_parent_line, $fk_fournprice, $pa_ht, $label, $array_options, $situation_percent, $fk_prev_id, $fk_unit, $pu_ht_devise);
					}
					else
					{
						$res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product);
					}
					
				}
				
				
			}
			
			echo 1;
			
			break;
		default:
			
			break;
	}

function _products($fk_parent=0, $is_supplier = 0, $productKeyword = '') {
	global $db,$conf,$langs;

	if(empty($fk_parent)) return array();
	
	$parent = new Categorie($db);
	$parent->fetch($fk_parent);
	
	$TProdNew = $parent->getObjectsInCateg('product');
	$TProd = array();

	/** @var Product $prod */
	foreach($TProdNew as $prod)
	{
		if (empty($is_supplier) && empty($prod->status)) continue;

		if (! empty($is_supplier) && empty($prod->status_buy)) continue;

		if (! empty($productKeyword))
		{
			// List des champs dans lesquels on fait la recherche
			$TElementsThatCanMatch = array($prod->ref, $prod->label, $prod->description, $prod->accountancy_code_buy, $prod->accountancy_code_sell);

			// preg_grep() retourne tous les élements du tableau passé en 2ème paramètre qui correspondent au pattern
			$TMatches = preg_grep('@' . preg_quote($productKeyword, '@') . '@i', $TElementsThatCanMatch);

			if (empty($TMatches)) continue;
		}

	    $TProd[] = $prod;
	}
	
	
	if (!empty($conf->global->SPC_DISPLAY_DESC_OF_PRODUCT))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
		foreach ($TProd as &$o) $o->description = dol_html_entity_decode($o->description, ENT_QUOTES);
	}
	if(!empty($conf->global->PRODUCT_USE_UNITS)){
		foreach ($TProd as &$o){
			$unit = $o->getLabelOfUnit();
			$o->unit = $langs->trans($unit);
		}
	}
	
	return $TProd;
}

function _categories($fk_parent=0, $keyword='') {
	global $db,$conf;
	$TFille=array();
	if(!empty($keyword))
	{
		$sql = 'SELECT rowid
				FROM ' . MAIN_DB_PREFIX. 'categorie
				WHERE type = 0
				AND label LIKE "%' . $db->escape($keyword) . '%"
				ORDER BY label';

		$resultset = $db->query($sql);

		if ($resultset !== false)
		{
			while ($obj = $db->fetch_object($resultset)) {
				$cat = new Categorie($db);
				$cat->fetch($obj->rowid);
				$TFille[] = $cat;
			}
		}
	}
	else {
		$parent = new Categorie($db);
		if(empty($fk_parent)) {
			if(empty($conf->global->SPC_DO_NOT_LOAD_PARENT_CAT)) {
				$TFille = $parent->get_all_categories(0,true);	
			}
				
		}
		else {
			$parent->fetch($fk_parent);
			$TFille = $parent->get_filles();
		}
		
	}
	
	
	return $TFille;
}
