<?php


	require '../config.php';
	dol_include_once('/categories/class/categorie.class.php');
	dol_include_once('/product/class/product.class.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/commande/class/commande.class.php');
	dol_include_once('/compta/facture/class/facture.class.php');
	
	$get=GETPOST('get');
	$put=GETPOST('put');

	switch ($get) {
		case 'categories':
			$fk_parent = (int)GETPOST('fk_parent');
			$keyword= GETPOST('keyword');
			$fk_soc = GETPOST('fk_soc');
			
			$Tab =array(
				'TCategory'=>_categories($fk_parent, $keyword)
				,'TProduct'=>_products($fk_parent)
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
					}
					if (empty($price)) $price = $p->price;
					if($object_type == 'facture'){
						$res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product, 0, '', '', 0, 0, '', 'HT',0, Facture::TYPE_STANDARD, -1, 0, '',0, 0, null, 0, '', 0, 100, '', $p->fk_unit , 0);
					}elseif($object_type == 'propal'){
						$res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product,0.0, 'HT', 0.0,0, 0, -1, 0,0, 0, 0, '','', '',0, $p->fk_unit);
					}elseif($object_type == 'commande'){
						$res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product, 0, 0, 0, 'HT', 0, '', '', 0, -1, 0, 0, null, 0, '',0, $p->fk_unit);
					}else{
						$res = $o->addline($p->description, $price, $qty, $txtva,0,0,$fk_product);
					}
					
				}
				
				
			}
			
			echo 1;
			
			break;
		default:
			
			break;
	}

function _products($fk_parent=0) {
	global $db,$conf,$langs;

	if(empty($fk_parent)) return array();
	
	$parent = new Categorie($db);
	$parent->fetch($fk_parent);
	
	$TProdNew = $parent->getObjectsInCateg('product');
	$TProd = array();
	foreach($TProdNew as $prod){
	    if($prod->status == 1) $TProd[] = $prod;
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
	if(!empty($keyword)) {
		$resultset = $db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."categorie WHERE label LIKE '%".addslashes($keyword)."%' ORDER BY label");		
		while($obj = $db->fetch_object($resultset)) {
			$cat = new Categorie($db);
			$cat->fetch($obj->rowid);
			$TFille[] = $cat;
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
