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
 * rememberwhen.action.php
 *
 * RememberWhen main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/rememberwhen/rememberwhen/myAction.html", ...)
 *
 */
  
  
  class action_rememberwhen extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "rememberwhen_rememberwhen";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there


    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */
        public function playCard()
    {
        self::setAjaxMode();     
        $card_id = self::getArg( "id", AT_posint, true );
        $this->game->playCard( $card_id );
        self::ajaxResponse( );
    }
    
    public function giveCards()
    {
        self::setAjaxMode();     
        $choice = self::getArg( "choice", AT_alphanum, true );
        
        $this->game->giveCards( $choice );
        self::ajaxResponse( );  

    }
      
    public function chooseRandomObject()
    {
        self::setAjaxMode();     
        $choice = self::getArg( "choice", AT_posint, true );
        
        $this->game->chooseRandomObject( $choice );
        self::ajaxResponse( );    
    }
       
    public function chooseAction()
    {
        self::setAjaxMode();     
        $choice = self::getArg( "choice", AT_alphanum, true );
        
        $this->game->chooseAction( $choice );
        self::ajaxResponse( );    
    }
           
    public function chooseRole()
    {
        self::setAjaxMode();     
        $choice = self::getArg( "choice", AT_alphanum, true );
        
        $this->game->chooseRole( $choice );
        self::ajaxResponse( );    
    }        
    public function arrangeSentence()
    {
        self::setAjaxMode();     
        $card_ids_raw = self::getArg( "choices", AT_numberlist, true );
        
        // Removing last ';' if exists
        if( substr( $card_ids_raw, -1 ) == ';' )
            $card_ids_raw = substr( $card_ids_raw, 0, -1 );
        if( $card_ids_raw == '' )
            $card_ids = array();
        else
            $pairs = explode( ';', $card_ids_raw );
        //throw new BgaSystemException ( "Pairs: ".implode(" ",$pairs));
       
        $choices = array("test" => "success");
        foreach ($pairs as $pair) {
            $data = explode(",", $pair);
            //throw new BgaSystemException ( "Data: ".implode(" ",$data));
            $choices[$data[0]] = $data[1];
        
            //throw new BgaSystemException ( "Choice: ".$data[0]." ".$choices[$data[0]]);
       
        }
        
        $this->game->arrangeSentence( $choices );
        self::ajaxResponse( );    
    }
         
    public function vote()
    {
        self::setAjaxMode();     
        $choice = self::getArg( "choice", AT_posint, true );
        
        $this->game->vote( $choice );
        self::ajaxResponse( );    
    }   


  }
  

