<?php

if (!defined('_PS_VERSION_'))
{
	exit;
}

class BdArtistSales extends Module
{

	public function __construct()
	{
		$this->name = 'bdartistsales';
		$this->tab = 'front_office_features';
		$this->version = '1.0';
		$this->author = 'Benoit Debordeaux';
		$this->controllers = array('display');
		$this->need_instance = 0;
		$this->ps_versions_compliancy = array('min' => '1.6');
		parent::__construct();
		$this->displayName = $this->l('Ventes de l\'artiste');
		$this->description = $this->l('Ce module permet d\'afficher les ventes de l\'artiste.');
		$this->confirmUninstall = $this->l('Etes-vous sûr de vouloir désinstaller le module?');
	}


	public function install()
	{
		if (Shop::isFeatureActive())
		Shop::setContext(Shop::CONTEXT_ALL);
		return parent::install() &&
		$this->registerHook('displayHeader') 
		&& $this->registerHook('displayNav') 
		&& $this->registerHook('DisplayMyAccountBlock')
		&& $this->registerHook('DisplayMyAccountBlockFooter')
		&& $this->registerHook('customerAccount')
		&& Db::getInstance()->query('
		CREATE TABLE IF NOT EXISTS '._DB_PREFIX_.'bd_artist_sales (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`name` varchar(254) NOT NULL,
			`value` float NOT NULL,
			PRIMARY KEY (`id`),
			KEY `name` (`name`),
			KEY `value` (`value`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8'
		);
	}


	public static function get($name)
	{
		return Db::getInstance()->getValue("
			SELECT value
			FROM "._DB_PREFIX_."bd_artist_sales
			WHERE name = '$name'"
			);
	}


	public static function updateValue($name, $value)
	{
		$name2 = 
		Db::getInstance()->getValue("
			SELECT name
			FROM "._DB_PREFIX_."bd_artist_sales
			WHERE name = '$name'"
		);

		if($name2)
		{
			Db::getInstance()->query("
				UPDATE "._DB_PREFIX_."bd_artist_sales
				SET value = $value
				WHERE name = '$name'"
			);
		}
		else
		{
			Db::getInstance()->query("
				INSERT INTO "._DB_PREFIX_."bd_artist_sales (
					name,
					value
				)
				VALUES (
					'$name',
					$value
				)"
			);
		}
	}


	public function uninstall() // la table bd_artist_sales n'est pas effacée par sécurité.
	{
		return parent::uninstall();
	}


	public function hookDisplayNav()
	{
		if($this->isEmployee() == true)
		{
			return $this->display(__FILE__, 'bdartistsalesnav.tpl');
		}	
	}


	public function hookDisplayMyAccountBlock()
	{
		if($this->isEmployee() == true)
		{
			return $this->display(__FILE__, 'bdartistsalesmyaccount1.tpl');
		}
	}


	public function hookDisplayMyAccountBlockFooter()
	{
		if($this->isEmployee() == true)
		{
			return $this->display(__FILE__, 'bdartistsalesmyaccount2.tpl');
		}
	}


	public function hookCustomerAccount($params)
	{
		if($this->isEmployee() == true)
		{
			return $this->display(__FILE__, 'bdartistsalesmyaccount3.tpl');
		}
	}


	public function hookDisplayHeader()
	{
		$this->context->controller->addCSS($this->_path.'css/bdartistsales.css', 'all');
	}

	public function getContent()
	{
		$output = null;
		if (Tools::isSubmit('submit'.$this->name))
		{
			$isvalid1 = false;
			$isvalid2 = false;
			$artist_names = $this->getArtistNames();
			foreach ($artist_names as $artist_name)
			{
				$tax = (float)(Tools::getValue('TAX_'.$artist_name['id_category']));
				if ($tax < 0 || $tax > 100)
				{
					$output .= $this->displayError($this->l('Taxe (%) pour ').$artist_name['name'].$this->l(' : valeur comprise entre 0 et 100.'));
					$isvalid1 = false;
					break;
				}
				else
				{
					$this->updateValue('TAX_'.$artist_name['id_category'], $tax);
					$isvalid1 = true;
				}

				$productNames = $this->getProductNamesWithArtistName($artist_name['name']);
				foreach ($productNames as $productName) {

					$percentage = (float)(Tools::getValue('PERCENT_'.$artist_name['id_category'].'_'.$productName['id_product']));
					
					if ($percentage < 0 || $percentage > 100)
					{Tools::
						$output .= $this->displayError($productName['name'].$this->l(' (%), pour l\'artiste ').$artist_name['name'].$this->l(' : valeur comprise entre 0 et 100.'));
						$isvalid2 = false;
						break;
					}
					else
					{
						$this->updateValue('TAX_'.$artist_name['id_category'], $tax);
						$this->updateValue('PERCENT_'.$artist_name['id_category'].'_'.$productName['id_product'], $percentage);
						$isvalid2 = true;
					}
				}
			}
			if($isvalid1 && $isvalid2)
			{
				$output .= $this->displayConfirmation($this->l('Settings updated'));
			}	
		}
		return $output.$this->displayForm();
	}

	
	public function displayForm()  // Formulaire de l'admin dans lequel on indique la taxe de l'artiste et les pourcentages de l'artistes sur ses produits.
	{
		// Get default Language
		$default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
		// Init Fields form array
		$artist_names = $this->getArtistNames();

		$fields_form[0]['form'] = 
		array(
			'legend' => 
			array(
				'title' => $this->l('Settings'), 
			),
			'submit' => 
			array(
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

		foreach ($artist_names as $index => $artist_name) {

			$fields_form[$index+1]['form'] =
			array(
				'legend' => 
				array(
					'title' => $artist_name['name'].' ('.$this->getCategoryName($artist_name['id_parent']).')',
				),
				'input' => 
				array(
					array(
						'type' => 'text',
						'label' => $this->l('Taxe (%)'),
						'name' => 'TAX_'.$artist_name['id_category'],
						'size' => 20,
						'required' => true
					)
				)
			);
	
			$productNames = $this->getProductNamesWithArtistName($artist_name['name']);
			$productNameArray = [];
			$productNamesArray = [];

			foreach ($productNames as $productName) 
			{
				$productNameArray = 				
					array(
						'type' => 'text',
						'label' => $productName['name'].$this->l(' (%)'),
						'name' => 'PERCENT_'.$artist_name['id_category'].'_'.$productName['id_product'],
						'size' => 20,
						'required' => true
					);
				array_push($productNamesArray, $productNameArray);			
			}

			foreach ($productNamesArray as $productNameArray) 
			{
				array_push($fields_form[$index+1]['form']['input'], $productNameArray);
			}
		}

		$helper = new HelperForm();
		// Module, token and currentIndex
		$helper->module = $this;
		$helper->name_controller = $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		// Language
		$helper->default_form_language = $default_lang;
		$helper->allow_employee_form_lang = $default_lang;
		// Title and toolbar
		$helper->title = $this->displayName;
		$helper->show_toolbar = true; // false -> remove toolbar
		$helper->toolbar_scroll = true; // yes - > Toolbar is always visible on the top of the screen.
		$helper->submit_action = 'submit'.$this->name;
		$helper->toolbar_btn = 
		array(
			'save' =>
			array(
				'desc' => $this->l('Save'),
				'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
				'&token='.Tools::getAdminTokenLite('AdminModules'),
			)
		);

		// Load current value
		foreach ($artist_names as $artist_name) {

			$helper->fields_value['TAX_'.$artist_name['id_category']] = $this->get('TAX_'.$artist_name['id_category']);
			$productNames = $this->getProductNamesWithArtistName($artist_name['name']);/**/
			foreach ($productNames as $productName) 
			{
				$helper->fields_value['PERCENT_'.$artist_name['id_category'].'_'.$productName['id_product']] = $this->get('PERCENT_'.$artist_name['id_category'].'_'.$productName['id_product']);
			}
		}
		return $helper->generateForm($fields_form);
	}


	public static function getArtistNames() // Recupération du nom des artistes, c'est à dire le nom des sous-catégories des catégories Collab, Merch et Marques.
	{
		return Db::getInstance()->executeS('
			SELECT name, '._DB_PREFIX_.'category_lang.id_category, id_parent
			FROM '._DB_PREFIX_.'category_lang
			INNER JOIN '._DB_PREFIX_.'category
			ON '._DB_PREFIX_.'category_lang.id_category = '._DB_PREFIX_.'category.id_category
			WHERE id_parent IN (12, 18, 26) '
		); 
	}

	public static function getCategoryName($id_category)
	{
		return Db::getInstance()->getValue('
			SELECT name
			FROM '._DB_PREFIX_.'category_lang
			WHERE id_category = '.(int)$id_category
		);
	}


	public static function getEmployeeEmails()
	{
		return Db::getInstance()->executeS('
			SELECT email
			FROM '._DB_PREFIX_.'employee'
		);
	}


	public static function getOrdersWithArtistEmail($email) // Fonction utilisée dans controllers\front\display.php. 
	{
		$artist_name = 
		Db::getInstance()->getValue("
			SELECT lastname
			FROM "._DB_PREFIX_."employee
			WHERE email = '$email'"
		); // Le nom de l'artiste correspond à la valeur indiquée dans le champs Nom dans Admin/Administration/Employés.

		$id_category = 
		Db::getInstance()->getValue("
			SELECT id_category
			FROM "._DB_PREFIX_."category_lang
			WHERE name = '$artist_name'"
		); // L'artiste a une catégorie qui porte son nom.

		return Db::getInstance()->executeS('
			SELECT product_price, invoice_date, id_category, product_id, product_quantity, product_name, reduction_amount, reduction_percent, total_products_wt, total_discounts_tax_incl, '._DB_PREFIX_.'order_detail.id_order 
			FROM '._DB_PREFIX_.'order_detail 
			INNER JOIN '._DB_PREFIX_.'category_product 
			ON '._DB_PREFIX_.'order_detail.product_id = '._DB_PREFIX_.'category_product.id_product
			INNER JOIN '._DB_PREFIX_.'orders 
			ON '._DB_PREFIX_.'order_detail.id_order = '._DB_PREFIX_.'orders.id_order
			WHERE id_category = '.(int)$id_category.
			' ORDER BY invoice_date'
		);
	}


	public static function getProductNamesWithArtistName($artist_name)
	{
		$id_category = 
		Db::getInstance()->getValue("
			SELECT id_category
			FROM "._DB_PREFIX_."category_lang
			WHERE name = '$artist_name'"
		); // L'artiste a une catégorie qui porte son nom.

		return Db::getInstance()->executeS('
			SELECT name, '._DB_PREFIX_.'category_product.id_product, id_category 
			FROM '._DB_PREFIX_.'category_product
			INNER JOIN '._DB_PREFIX_.'product_lang
			ON '._DB_PREFIX_.'category_product.id_product = '._DB_PREFIX_.'product_lang.id_product
			WHERE id_category = '.(int)$id_category
		);
	}

	public function isEmployee() // L'artiste crée son compte sur la boutique. Il est reconnu par l'adresse email indiquée dans Admin/Administration/Employés, "Mes ventes" apparaît dans la boutique.
	{
		$employee_emails = $this->getEmployeeEmails();
		$customer_email = $this->context->cookie->email;
		$is_employee = false;

		foreach ($employee_emails as $employee_email) 
		{
			if ($employee_email['email'] == $customer_email ) 
			{
				$is_employee = true;
			}
		}

		if($is_employee)
		{
			$this->context->smarty->assign(
				array(
					'bd_artist_sales_link' => $this->context->link->getModuleLink('bdartistsales', 'display'),
				)
			);
			return true;
		}
		else
		{
			return false;
		}
	}
}




