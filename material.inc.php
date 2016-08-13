<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * RememberWhen implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * RememberWhen game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/

$this->colors = array(
			1 => array( 'name' => clienttranslate('When'),
						'nametr' => self::_('When'),
						'num_cards' => 200/4),
			2 => array( 'name' => clienttranslate('Where'),
						'nametr' => self::_('Where'),
						'num_cards' => 408/4),
			3 => array( 'name' => clienttranslate('How'),
						'nametr' => self::_('How') ,
						'num_cards' => 204/4),
			4 => array( 'name' => clienttranslate('Did What'),
						'nametr' => self::_('Did What') ,
						'num_cards' => 412/4),
			5 => array( 'name' => clienttranslate('Whose'),
						'nametr' => self::_('Whose'),
						'num_cards' => 212/4),
			6 => array( 'name' => clienttranslate('What Kind'),
						'nametr' => self::_('What Kind'),
						'num_cards' => 404/4),
			7 => array( 'name' => clienttranslate('To What'),
						'nametr' => self::_('To What'),
						'num_cards' => 508/4 ),
			8 => array( 'name' => clienttranslate('Why'),
						'nametr' => self::_('Why'),
						'num_cards' => 414/4 )
		);




