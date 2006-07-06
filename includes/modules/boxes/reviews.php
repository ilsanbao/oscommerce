<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2006 osCommerce

  Released under the GNU General Public License
*/

  class osC_Boxes_reviews extends osC_Modules {
    var $_title,
        $_code = 'reviews',
        $_author_name = 'osCommerce',
        $_author_www = 'http://www.oscommerce.com',
        $_group = 'boxes';

    function osC_Boxes_reviews() {
      global $osC_Language;

      $this->_title = $osC_Language->get('box_reviews_heading');
      $this->_title_link = tep_href_link(FILENAME_PRODUCTS, 'reviews');
    }

    function initialize() {
      global $osC_Database, $osC_Services, $osC_Cache, $osC_Language, $osC_Product, $osC_Image;

      if ($osC_Services->isStarted('reviews')) {
        if ((BOX_REVIEWS_CACHE > 0) && $osC_Cache->read('box-reviews' . (isset($osC_Product) && is_a($osC_Product, 'osC_Product') && $osC_Product->isValid() ? '-' . $osC_Product->getID() : '') . '-' . $osC_Language->getCode(), BOX_REVIEWS_CACHE)) {
          $data = $osC_Cache->getCache();
        } else {
          $data = array();

          $Qreview = $osC_Database->query('select r.reviews_id, r.reviews_rating, p.products_id, pd.products_name, pd.products_keyword, i.image from :table_reviews r, :table_products p left join :table_products_images i on (p.products_id = i.products_id and i.default_flag = :default_flag), :table_products_description pd where r.products_id = p.products_id and p.products_status = 1 and r.languages_id = :language_id and p.products_id = pd.products_id and pd.language_id = :language_id and r.reviews_status = 1');
          $Qreview->bindTable(':table_reviews', TABLE_REVIEWS);
          $Qreview->bindTable(':table_products_images', TABLE_PRODUCTS_IMAGES);
          $Qreview->bindTable(':table_products', TABLE_PRODUCTS);
          $Qreview->bindTable(':table_products_description', TABLE_PRODUCTS_DESCRIPTION);
          $Qreview->bindInt(':default_flag', 1);
          $Qreview->bindInt(':language_id', $osC_Language->getID());
          $Qreview->bindInt(':language_id', $osC_Language->getID());

          if (isset($osC_Product) && is_a($osC_Product, 'osC_Product') && $osC_Product->isValid()) {
            $Qreview->appendQuery('and p.products_id = :products_id');
            $Qreview->bindInt(':products_id', $osC_Product->getID());
          }

          $Qreview->appendQuery('order by r.reviews_id desc limit :max_random_select_reviews');
          $Qreview->bindInt(':max_random_select_reviews', BOX_REVIEWS_RANDOM_SELECT);
          $Qreview->executeRandomMulti();

          if ($Qreview->numberOfRows()) {
            $Qtext = $osC_Database->query('select substring(reviews_text, 1, 60) as reviews_text from :table_reviews where reviews_id = :reviews_id and languages_id = :languages_id');
            $Qtext->bindTable(':table_reviews', TABLE_REVIEWS);
            $Qtext->bindInt(':reviews_id', $Qreview->valueInt('reviews_id'));
            $Qtext->bindInt(':languages_id', $osC_Language->getID());
            $Qtext->execute();

            $data = array_merge($Qreview->toArray(), $Qtext->toArray());

            $Qtext->freeResult();
            $Qreview->freeResult();
          }

          $osC_Cache->writeBuffer($data);
        }

        $this->_content = '';

        if (empty($data)) {
          if (isset($osC_Product) && is_a($osC_Product, 'osC_Product') && $osC_Product->isValid()) {
            $this->_content = '<table border="0" cellspacing="0" cellpadding="2">' . "\n" .
                              '  <tr>' . "\n" .
                              '    <td class="infoBoxContents"><a href="' . tep_href_link(FILENAME_PRODUCTS, 'reviews=new&' . $osC_Product->getKeyword()) . '">' . tep_image(DIR_WS_IMAGES . 'box_write_review.gif', $osC_Language->get('button_write_review')) . '</a></td>' . "\n" .
                              '    <td class="infoBoxContents"><a href="' . tep_href_link(FILENAME_PRODUCTS, 'reviews=new&' . $osC_Product->getKeyword()) . '">' . $osC_Language->get('box_reviews_write') .'</a></td>' . "\n" .
                              '  </tr>' . "\n" .
                              '</table>' . "\n";
          }
        } else {
          if (empty($data['image']) === false) {
            $this->_content = '<div align="center"><a href="' . tep_href_link(FILENAME_PRODUCTS, 'reviews=' . $data['reviews_id'] . '&' . $data['products_keyword']) . '">' . $osC_Image->show($data['image'], $data['products_name']) . '</a></div>';
          }

          $this->_content .= '<a href="' . tep_href_link(FILENAME_PRODUCTS, 'reviews=' . $data['reviews_id'] . '&' . $data['products_keyword']) . '">' . tep_break_string(tep_output_string_protected($data['reviews_text']), 15, '-<br />') . ' ..</a><br /><div align="center">' . tep_image(DIR_WS_IMAGES . 'stars_' . $data['reviews_rating'] . '.gif' , sprintf($osC_Language->get('box_reviews_stars_rating'), $data['reviews_rating'])) . '</div>';
        }
      }
    }

    function install() {
      global $osC_Database;

      parent::install();

      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Random Review Selection', 'BOX_REVIEWS_RANDOM_SELECT', '10', 'Select a random review from this amount of the newest reviews available', '6', '0', now())");
      $osC_Database->simpleQuery("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Cache Contents', 'BOX_REVIEWS_CACHE', '1', 'Number of minutes to keep the contents cached (0 = no cache)', '6', '0', now())");
    }

    function getKeys() {
      if (!isset($this->_keys)) {
        $this->_keys = array('BOX_REVIEWS_RANDOM_SELECT', 'BOX_REVIEWS_CACHE');
      }

      return $this->_keys;
    }
  }
?>