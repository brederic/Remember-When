/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * RememberWhen implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * rememberwhen.js
 *
 * RememberWhen user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],

 

    function (dojo, declare) {
        return declare("bgagame.rememberwhen", ebg.core.gamegui, {
            constructor: function () {
                console.log('rememberwhen constructor');

                // Here, you can init the global variables of your user interface
                // Example:
                // this.myGlobalValue = 0;
                this.playerHand = null;
                this.cardwidth = 150;
                this.cardheight = 150;
                this.currentState = '';
                this.selectedCard = 0;
                this.handConnection = null;
                this.roles =['Active', 'Hero', 'Villain'];
                this.sentenceBuilder;
                this.currentSentence = null;
                this.connects = {};
                this.contributionMap = null;
               
            },

            /*
                setup:
                
                This method must set up the game user interface according to current game situation specified
                in parameters.
                
                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)
                
                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gamedatas) {
                console.log("Starting game setup");

                console.log("start creating player boards");
                this.sentenceBuilder = this.gamedatas.sentence_builder;

                console.log("Sentence Builder: "+ this.gamedatas.sentence_builder);
                console.log("Role: "+ this.gamedatas.role);
                console.log('Contribution map:');
                console.log(gamedatas.contribution);
                this.contributionMap = gamedatas.contribution;
                 // Setting up player boards
                for( var id in gamedatas.contribution )
                {
                    console.log(id);
                    pid = id;
                    var player = gamedatas.players[pid];

                    if (pid == this.sentenceBuilder) {     
                        // Setting up players boards if needed
                        var player_board_div = $('player_board_'+this.sentenceBuilder);
                        if (this.gamedatas.role ==1) {
                            color = 'H';
                        } else if (this.gamedatas.role ==2 ) {
                            color = 'V';
                        } else {
                            color = 'A';
                        }
                        dojo.place( this.format_block('jstpl_role', {
                                player: this.sentenceBuilder,
                                color: color,
                                role: ''
                            }    ), player_board_div );
                    } else {
                        data = {
                                player: pid,
                                color: gamedatas.contribution[id]['contribution'],
                                role: ''
                            } ;
                        console.log(data);
                        var player_board_div = $('player_board_'+pid);
                        dojo.place( this.format_block('jstpl_role', data ), player_board_div );
                    }
                }



                // Player hand
                this.playerHand = new ebg.stock();
                this.playerHand.create(this, $('myhand'), this.cardwidth, this.cardheight);
                this.playerHand.image_items_per_row = 8;
                this.playerHand.setSelectionMode(1);
                this.playerHand.setSelectionAppearance( 'class' );
                this.handConnection = dojo.connect(this.playerHand, 'onChangeSelection', this, 'onPlayerHandSelectionChanged');


                console.log("Build sentences");
                               
                console.log( "Add interactions to cards" );
                this.hookupCardsOnLoad();
               
                
                console.log("Create hand card types");

                // Create cards types:
                for (var color = 1; color <= 8; color++) {

                    // Build card type
                    this.playerHand.addItemType(color, color, g_gamethemeurl + 'img/fronts-sm.png', color - 1);
                    
                }
                // Cards in player's hand 
                
                 
                // Hide hand of sentence builder, except actions
                if (this.sentenceBuilder == this.player_id) {
                     console.log("I am the sentence builder")
                    for (var i in this.gamedatas.action_choice) {
                        var card = this.gamedatas.action_choice[i];
                        console.log(card);
                        var color = card.type;
                        var value = card.type_arg;
                        var card_id = this.getCardUniqueId(color, value);
                        //console.log('Hand Card: ');
                        //console.log(card);
                        this.playerHand.addToStockWithId(color, card_id);
                        // add text to card
                        this.playCardInHand(card_id, card, 'hand_' + card.id);

                    }
                      
                    
                        
                } else {
                    for (var i in this.gamedatas.hand) {
                        var card = this.gamedatas.hand[i];
                        var color = card.type;
                        var value = card.type_arg;
                        var card_id = this.getCardUniqueId(color, value);
                        console.log('Hand Card: ');
                        console.log(card);
                        this.playerHand.addToStockWithId(color, card_id);
                        // add text to card
                        this.playCardInHand(card_id, card, 'hand_' + card.id);
                    }
                }             
                console.log('Hand:')
                console.log($('myhand'));

                // Cards in top sentence
                for (var i in this.gamedatas.top_sentence) {
                    var card = this.gamedatas.top_sentence[i];
                    var color = card.type;
                    var value = card.type_arg;
                    var card_id = this.getCardUniqueId(color, value);
                        
                    this.playCardOnTable(card, 'top_sentence', card.location_arg, id);

                    
                }

                console.log('Current Sentence:');
                console.log(this.gamedatas.current_sentence);

                // Cards in current sentence
                this.currentSentence = gamedatas.current_sentence;
                //start with current sentence visible 
                dojo.addOnLoad( function() {
                    target = $('firstTab');
                    var evt = { currentTarget: target};
                    showSentence(evt,'current_sentence');
                    });
                for (var i in this.gamedatas.current_sentence) {
                    
                    var card = this.gamedatas.current_sentence[i];
                    var id = 'game_' + this.getCardUniqueId(color, value);

                    // verb and object are fixed
                    if (card.location_arg <= 4) {
                        this.playCardOnTable(card, 'current_sentence', card.location_arg, id,'' );
                    } else {
                        this.playCardOnTable(card, 'current_sentence', 1, id,'' );
                    }
                   
                    this.hideCardsOfType(card.type);
                    console.log('Hand:')
                    console.log($('myhand'));
                }
                
                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                this.ensureSpecificImageLoading(['../common/point.png']);
                console.log('Hand:')
                console.log($('myhand'));
                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);
                this.currentState = stateName;

                switch (stateName) {
                    case 'playerTurn':
                        this.addTooltip('myhand', _('Cards in my hand'), _('Play a card'));
                        break;

                    case 'giveCards':
                        this.addTooltip('myhand', _('Cards in my hand'), _('Select/Rotate a card'));
                        break;
                    
                    case 'chooseAction':
                        cards = dojo.query('div[id^="cardontable"]');
                        console.log(cards);
                       
                    case 'vote':
                        // clear all connections
                        var nodes = dojo.query('div[class*="rotatable"]');
                        var self = this;
                        nodes.forEach( function (node, idx) {
                            dojo.forEach( self.connects[node.id], function( handle ) {
                                dojo.disconnect( handle );
                            });
                        });
                            
                        dojo.query('.reverse').removeClass('reverse');
                        dojo.query('.rotatable').removeClass('rotatable');
                        dojo.query('.invisible').removeClass('invisible');
                        var map = this.contributionMap;

                        // display all contributions
                        for( var id in map )
                        {
                            console.log(id);
                            
                        

                            if (id != this.sentenceBuilder) {     
                               role_icon_id = 'role_icon_p'+ id;
                               console.log(role_icon_id);
                                require(["dojo/html"], function(html){
                                    html.set(role_icon_id, map[id]['guess']);
                                });
                            
                            }
                        }
                        
                        

                    case 'dummmy':
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {

                    /* Example:
                    
                    case 'myGameState':
                    
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        
                        break;
                   */


                    case 'dummmy':
                        break;
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        
                    case 'chooseRandomObject':
                        this.addActionButton( 'chooseRandomObject_button1', _('1'), 'onChooseRandomObject' ); 
                        this.addActionButton( 'chooseRandomObject_button2', _('2'), 'onChooseRandomObject' ); 
                        this.addActionButton( 'chooseRandomObject_button3', _('3'), 'onChooseRandomObject' ); 
                        this.addActionButton( 'chooseRandomObject_button4', _('4'), 'onChooseRandomObject' ); 
                        break;
                    
                        case 'chooseAction':
                           
                                this.addActionButton('Select', 'Select', 'onChooseAction');

                           

                            break;
                        case 'chooseRole':

                                this.addActionButton('1', 'Hero', 'onChooseRole');
                                this.addActionButton('2', 'Villain', 'onChooseRole');


                            break;
                        case 'giveCards':
                              this.addActionButton('Select', 'Select', 'onGiveCard'); 
                            break;
                        case 'arrangeSentence':
                              this.addActionButton('Submit', 'Submit', 'onArrangeSentence'); 
                            break;
                         case 'vote':
                         case 'tieBreak':
                              this.addActionButton('1', 'Top Sentence', 'onVote'); 
                              this.addActionButton('2', 'Current Sentence', 'onVote'); 

                            break;
                        
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            /*
            
                Here, you can defines some utility methods that you can use everywhere in your javascript
                script.
            
            */

       

        makeConnections: function (node) {
            events =  ['click','ondblclick' ];
            var self = this;
            events.forEach( function (evt, idx) {
                if (!(node.id in self.connects)) {
                    self.connects[node.id] = [];
                } else { // don't make duplicate connections
                    return;
                }
                self.connects[ node.id ][idx] = dojo.connect( node, evt, function(evt) { 
                        // 'this' is now the element clicked on (e.g. id="textDiv")
                        var el = this; 
                        
                        console.log('onClick ' + this.id  ); 
        
                        
                        if (dojo.hasClass(this.id, 'pos_1')) {
                            dojo.replaceClass(this.id, 'pos_2', 'pos_1');
                        } else if (dojo.hasClass(this.id, 'pos_2')) {
                            dojo.replaceClass(this.id, 'pos_3', 'pos_2');
                        } else if (dojo.hasClass(this.id, 'pos_3')) {
                            dojo.replaceClass(this.id, 'pos_4', 'pos_3');
                        } else if (dojo.hasClass(this.id, 'pos_4')) {
                            dojo.replaceClass(this.id, 'pos_1', 'pos_4');
                        } 
                });
            
            });
           
        },

        hookupCardsOnLoad:  function () {
            var self = this;

             dojo.addOnLoad( function() {
                    console.log("onLoad executing...")
                    
                  // attach on click to id="textDiv"
                  var nodes = dojo.query('div[class*="rotatable"]');
                  nodes.forEach( function (node, idx) {
                     
                      self.makeConnections(node);
                     });   
             });
        },
            		
		hideCardsOfType: function (playedCardType)
		{
            console.log('Making cards invisible of type:' + playedCardType);
            
			for (var i in this.playerHand.getAllItems()) {
				c = this.playerHand.getAllItems()[i]
				//console.log(c);
                card_block = dojo.query('div[id=myhand_item_'+c['id']+'] > div')[0];
                //console.log(card_block);
				type = dojo.getAttr(card_block, 'type')//this.getCardType(c['id']);
				
                
				if (type == playedCardType) {
					var matchingCard = c;
                    // check if this card is currently selected and unselect it
                    if (dojo.getAttr(card_block, 'id') == this.selectedCard) {
						this.playerHand.unselectAll();
					}
					var id = 'myhand_item_'+matchingCard['id'];
					console.log( 'Make invisible card of id: '+ c['id']+ ', type: ' + type );
					console.log( 'id: ' + id );
                    console.log(card_block);
					require(["dojo"], function(dojo){
						dojo.addClass(id, "invisible");
					});

				} else {
					//console.log( 'Keep visible card of id: '+ c['id']+ ', type: ' + type );
				}
			}
		},

             getCursorPosition: function (canvas, event) {
                var rect = canvas.getBoundingClientRect();
                var x = event.clientX - rect.left;
                var y = event.clientY - rect.top;
                console.log("x: " + x + " y: " + y);
            },
             onCardClick: function (card_block) {
         
                	var id = dojo.getAttr(card_block, 'id');
                    console.log('onClick ' + id  + dojo.getAttr(card_block, 'classes')); 
    
                	
                    if (dojo.hasClass(id, 'pos_1')) {
                        dojo.replaceClass(id, 'pos_2', 'pos_1');
                    } else if (dojo.hasClass(id, 'pos_2')) {
                        dojo.replaceClass(id, 'pos_3', 'pos_2');
                    } else if (dojo.hasClass(id, 'pos_3')) {
                        dojo.replaceClass(id, 'pos_4', 'pos_3');
                    } else if (dojo.hasClass(id, 'pos_4')) {
                        dojo.replaceClass(id, 'pos_1', 'pos_4');
                    } 
                  },

            // Get card unique identifier based on its color and value
            getCardUniqueId: function (color, value) {
                return (color) * 1000 + (value );

            },

            getCardType: function (id) {
                value = this.getCardValue(id);
                return Math.floor((id - value + 14) / 13);
            },

            getCardValue: function (id) {
                return (id - 1) % 13 + 2;
            },

            playCardInHand: function (card_id, card, card_name) {
                var color = card.type;
                var value = card.type_arg;
                var card_id = this.getCardUniqueId(color, value);
                this.playerHand.addToStockWithId(color, card_id);
                console.log('playCardInHand(' + card_name + ', ' + color + ', ' + card_id + ')');
                console.log(card);
                

                // get card div
                div_id = $('myhand_item_' + card_id);
                // add card text
                //'test';
                card_block = this.format_block('jstpl_cardontable', {
                    x: this.cardwidth * (card.type - 1),
                    y: 0,
                    type: card.type,
                    player_id: card_name,
                    text_1: card.text_1,
                    text_2: card.text_2,
                    text_3: card.text_3,
                    text_4: card.text_4
                });
                console.log(div_id);
                if (div_id == null) {
                    console.error('No div to put card in!!')
                }
                dojo.place(card_block, div_id, "only");
                dojo.addClass(card_name, 'pos_1');
                
                
            },


            playCardOnTable: function (card,  loc, rotation, player_id, klass) {
                isActivePlayer = this.player_id == this.sentenceBuilder;
                // handle hidden cards in current sentence                
                if (loc == 'current_sentence' & card.type != '4' & card.type != '7') {
                    if (this.currentState != "vote") {
                        if (isActivePlayer) {     
                            klass = 'rotatable';
                        } else {
                            //play face down
                            klass ='reverse';
                        }
                    }
                } else {
                    klass ='';
                }
                var color = card.type;
                var value = card.type_arg;
                var card_id = color + "_" + value;
                
                card_name = loc + '_' + card.id; 
                
                //console.log('playCardOnTable(' + card_name + ', ' + color + ', ' + card_id + ', ' + loc + ')');
                //console.log(card);
                
                card_block = this.format_block('jstpl_cardontable', {
                    x: this.cardwidth * (color - 1),
                    y: 0,
                    type: card.type,
                    player_id: card_name,
                    text_1: card.text_1,
                    text_2: card.text_2,
                    text_3: card.text_3,
                    text_4: card.text_4
                });
                dest = 'spot_' + color;
                if (loc == 'top_sentence') {
                    dest = 'top_' + dest;
                }

                dojo.place(card_block, dest, "only"); 

                //this.placeOnObject(card_name, dest);
                dojo.addClass(card_name, 'pos_' + rotation);
                
                // In any case: move it to its final destination
                //console.log('Sliding to ' + dest);
                if (klass != '') {
                    dojo.addClass(card_name, klass);
                }
                if (klass == 'reverse') {
                    dojo.query('div[id='+card_name+'] > div').addClass('invisible');
                }
                if (loc == 'top_sentence') {
                //    dest = 'current_' + dest;
                }
                //this.slideToObject(card_name, dest).play();
                /*
                dojo.addOnLoad(function(){
                    dojo.connect(dojo.byId(card_name), "onclick", "onCardClick");
                });*/

            },



            ///////////////////////////////////////////////////
            //// Player's action

            /*
            
                Here, you are defining methods to handle player's action (ex: results of mouse click on 
                game objects).
                
                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server
            
            */
                    
        onChooseRandomObject: function ( evt)
        {
            console.log('onChooseRandomObject');
            dojo.stopEvent( evt );
            var choice = evt.currentTarget.id;
			choice = choice[choice.length-1];
            if( this.checkAction( 'chooseRandomObject' ) )
            {

                this.ajaxcall( "/rememberwhen/rememberwhen/chooseRandomObject.html", { choice: choice, lock: true }, this, function( result ) {
                }, function( is_error) { } );                
            }        
        }, 
                     
        onChooseRole: function ( evt)
        {
            console.log('onChooseRole');
            dojo.stopEvent( evt );
            var choice = evt.currentTarget.id;
            if( this.checkAction( 'chooseRole' ) )
            {

                this.ajaxcall( "/rememberwhen/rememberwhen/chooseRole.html", { choice: choice, lock: true }, this, function( result ) {
                }, function( is_error) { } );                
            }        
        },    
        getRotation: function(card_block) 
        {
            console.log(card_block);
            rotation = 0;
            for (var j = 1; j<=4;j++){
                    if (dojo.hasClass(card_block, 'pos_'+j)) {
                        rotation = j;
                        break;
                    }
                }
            return rotation;
        },
        onChooseAction: function( evt)
        {
            console.log('onChooseAction');
            if (this.selectedCard == 0) {
                this.showMessage("Please select an action card", "error");
                return;
            }
            dojo.stopEvent( evt );
            
            var choice = this.selectedCard;
            card_block = $(choice);
            
            choice = choice + "_" + this.getRotation(card_block);
            console.log('onChooseAction: button choice:' + choice);
            if( this.checkAction( 'chooseAction' ) )
            {

                this.ajaxcall( "/rememberwhen/rememberwhen/chooseAction.html", { choice: choice, lock: true }, this, function( result ) {
                }, function( is_error) { } );                
            }   
            this.playerHand.removeAll();
                 
        },    
        onVote: function( evt)
        {
            console.log('onVote');
           dojo.stopEvent( evt );
            var choice = evt.currentTarget.id;
            if( this.checkAction( 'vote' ) )
            {

                this.ajaxcall( "/rememberwhen/rememberwhen/vote.html", { choice: choice, lock: true }, this, function( result ) {
                }, function( is_error) { } );                
            }   
            
                 
        },    
        onGiveCard: function( evt)
        {
            console.log('onGiveCard');
            if (this.selectedCard == 0) {
                this.showMessage("Please select a card", "error");
                return;
            }
            dojo.stopEvent( evt );
            
            var choice = this.selectedCard;
            card_block = $(choice);
            
            choice = choice + "_" + this.getRotation(card_block);
            console.log('onGiveCard: ' + choice);
            if( this.checkAction( 'giveCards' ) )
            {

                this.ajaxcall( "/rememberwhen/rememberwhen/giveCards.html", { choice: choice, lock: true }, this, function( result ) {
                }, function( is_error) { } );                
            }   
            //this.playerHand.removeAll();
                 
        },    
         onArrangeSentence: function( evt)
        {
            console.log('onArrangeSentence');
                if( this.checkAction( 'arrangeSentence' ) )
                {
                
                // collect choices
                var choices = '';
                for (var i=1; i <=8; i++){
                    card = dojo.query("div[id=current_sentence] > div[id=spot_" +i + '] > div')[0];
                    if (card != null) {
                        choices += i+','+this.getRotation(card)+';';       
                    }
                }
                
                dojo.stopEvent( evt );
                
                
                console.log('onArrangeSentence: ');
                console.log( choices);
                if( this.checkAction( 'arrangeSentence' ) )
                {

                    this.ajaxcall( "/rememberwhen/rememberwhen/arrangeSentence.html", { choices: choices, lock: true }, this, function( result ) {
                    }, function( is_error) { } );                
                }   
            } 
            
                 
        },    
       

            onPlayerHandSelectionChanged: function (evt) {

                // get the cards that the stock thinks are highlighted                
                var items = this.playerHand.getSelectedItems();

                if (items.length > 0) { // a card was clicked 
                   
                    console.log('chooseAction with selection');
                    console.log(items[0]);
                    var id = items[0]['id'];

                    // get the card block
                    card_node = dojo.query('div[id=myhand_item_'+id+ '] > div')[0];
                    console.log(card_node);

                    if (card_node.id == this.selectedCard) { // the click was on the currently selected card
                        // rotate selected card
                        this.onCardClick(card_node);
                    } else { // we have a new selected card, it should be highlighted, not rotated
                        this.selectedCard = card_node.id;
                    }
                    


                } else { // the same card was click (the stock interpret this as a deselect)
                    // get the currently selected card
                    card_block = $(this.selectedCard);
                    // rotate selected card
                    this.onCardClick(card_block);
                   

                }
                // update selection styles
                console.log('fixing classes on hand stock');
                items = dojo.query('div[id^=hand_]');//this.playerHand.getAllItems();
                console.log(items);
                console.log('selected:' +this.selectedCard);
                items.forEach( function (item) {
                    console.log(item.id);
                    dojo.removeClass(item, 'myitem_selected');
                    }
                );
                dojo.addClass(this.selectedCard, 'myitem_selected');
            },



            /* Example:
            
            onMyMethodToCall1: function( evt )
            {
                console.log( 'onMyMethodToCall1' );
                
                // Preventing default browser reaction
                dojo.stopEvent( evt );
    
                // Check that this action is possible (see "possibleactions" in states.inc.php)
                if( ! this.checkAction( 'myAction' ) )
                {   return; }
    
                this.ajaxcall( "/rememberwhen/rememberwhen/myAction.html", { 
                                                                        lock: true, 
                                                                        myArgument1: arg1, 
                                                                        myArgument2: arg2,
                                                                        ...
                                                                     }, 
                             this, function( result ) {
                                
                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)
                                
                             }, function( is_error) {
    
                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)
    
                             } );        
            },        
            
            */


            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:
                
                In this method, you associate each of your game notifications with your local method to handle it.
                
                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your rememberwhen.game.php file.
            
            */
            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                dojo.subscribe('newRound', this, "notif_newRound");
                dojo.subscribe('dealing', this, "notif_deal");
                dojo.subscribe('newCard', this, "notif_newCard");
                dojo.subscribe('considerActions', this, "notif_considerActions");
                dojo.subscribe('giveAllCardsToPlayer', this, "notif_giveAllCardsToPlayer");
                dojo.subscribe('giveCards', this, "notif_giveCards");
                dojo.subscribe('cardGiven', this, "notif_cardGiven");
                dojo.subscribe('takeCards', this, "notif_takeCards");
                dojo.subscribe('addCardToSentence', this, "notif_addCardToSentence");
                dojo.subscribe('chooseRole', this, "notif_chooseRole");
                dojo.subscribe('score', this, "notif_updateScore");
                this.notifqueue.setSynchronous( 'score', 1000 );   // Wait 500 milliseconds after executing the playDisc handler

                this.notifqueue.setSynchronous( 'voteSentence', 3000 );   // Wait 500 milliseconds after executing the playDisc handler

                dojo.subscribe('revealCurrentSentence', this, "notif_revealCurrentSentence");
                this.notifqueue.setSynchronous( 'revealCurrentSentence', 2000 );   // Wait 500 milliseconds after executing the playDisc handler
    
                dojo.subscribe('newTop', this, "notif_newTopSentence");


            },

            // TODO: from this point and below, you can write your game notifications handling methods

            notif_deal: function (notif) {

                console.log('notifications deal');

            }, 
            notif_revealCurrentSentence: function (notif) {

                console.log('notifications revealCurrentSentence');
                this.currentSentence = notif.args.cards;
                this.contributionMap = notif.args.contributions;
                // rotate cards to their chosen positions
                        //console.log(this.currentSentence);
                for (var i in this.currentSentence) {
                    var card = this.currentSentence[i];
                    card_block = $('current_sentence_' + card.id);
                    dojo.removeClass(card_block, 'pos_1 pos_2 pos_3 pos_4');
                    dojo.addClass(card_block, 'pos_'+card.location_arg);
                }



            },
            
            notif_newTopSentence: function (notif) {

                console.log('notifications newTopSentence');
                
                // play new cards
                for (var i in notif.args.topSentence) {
                    var card = notif.args.topSentence[i];
                    var color = card.type;
                    var value = card.type_arg;
                    var card_id = this.getCardUniqueId(color, value);
                        
                    this.playCardOnTable(card, 'top_sentence', card.location_arg, card_id);

                    
                }

            },
            notif_newRound: function (notif) {
                console.log('notifications new round');
                this.sentenceBuilder = notif.args.active_player;
                // clear all contribution marks
                for (var player_id in this.gamedatas.players) {
                    role_icon_id = 'role_icon_p'+ player_id;
                    require(["dojo/html"], function(html){
                        html.set(role_icon_id, '');
                     });
                    dojo.removeClass(role_icon_id, 'role_icon_A role_icon_H role_icon_V role_icon_1 role_icon_2 role_icon_3 role_icon_4 role_icon_5 role_icon_6 role_icon_7 role_icon_8');
                    if (player_id == this.sentenceBuilder) {
                        dojo.addClass(role_icon_id, 'role_icon_A');
                    } else {
                        dojo.addClass(role_icon_id, 'role_icon_0');
                    }
                }

                // clear current sentence
                console.log('Clear Current sentence:');
                cards = dojo.query('div[id=current_sentence] div[id^=current_sentence_]');
                console.log(cards);
                for (var i in cards) {
                    var card = cards[i];
                    this.fadeOutAndDestroy(card);
                    
                }

            },
            notif_newCard: function (notif) {
            

                console.log('notifications new card');
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    console.log(card);
                    var color = card.type;
                    var value = card.type_arg;
                    //console.log(card);
                    //this.playerHand.addToStockWithId(color, this.getCardUniqueId(color, value));
                    // add text to card
                    this.playCardInHand(card.id, card, 'hand_' + card.id);
                }
            },
             notif_cardGiven: function (notif) {
            

                console.log('notifications cardGiven');
            
                var card = notif.args.card;
                var color = card.type;
                var value = card.type_arg;
                this.playerHand.removeFromStockById(this.getCardUniqueId(color, value));
                
            },
            notif_considerActions: function (notif) {
                console.log('notifications considerActions');
                
                 // Player hand
                this.playerHand.removeAll();
                

                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    var color = card.type;
                    var value = card.type_arg;
                    var rotation = 1; //?????
                    this.playCardInHand(card.id, card, 'hand_'+card.id);
                }         
            },
            notif_chooseRole: function (notif) {
                console.log('notifications chooseRole');
                
                role_div = dojo.query('div.role_icon_A')[0];
                console.log(notif.args);
                //console.log(role_div);

                dojo.removeClass(role_div, 'role_icon_A');
                if (notif.args.choice == '1') {
                    dojo.addClass(role_div, 'role_icon_H');
                } else {
                    dojo.addClass(role_div, 'role_icon_V');
                }
                
                
            },
            notif_trickWin: function (notif) {
                console.log('notifications trickWin');
                // We do nothing here (just wait in order players can view the 4 cards played before they're gone.
            },
            notif_giveAllCardsToPlayer: function (notif) {
                console.log('notifications giveAllCardsToPlayer');
                 // Move all cards on table to given table, then destroy them
                var winner_id = notif.args.player_id;
                for (var player_id in this.gamedatas.players) {
                    var anim = this.slideToObject('cardontable_' + player_id, 'overall_player_board_' + winner_id);
                    dojo.connect(anim, 'onEnd', function (node) { dojo.destroy(node); });
                    anim.play();
                }
            },
           
            notif_giveCards: function (notif) {
                console.log('notifications giveCards');
                // Remove cards from the hand (they have been given)
                for (var i in notif.args.cards) {
                    var card_id = notif.args.cards[i];
                    this.playerHand.removeFromStockById(card_id);
                }
            },
            notif_takeCards: function (notif) {
                console.log('notifications takeCards');
                // Cards taken from some opponent
                for (var i in notif.args.cards) {
                    var card = notif.args.cards[i];
                    var color = card.type;
                    var value = card.type_arg;
                    this.playerHand.addToStockWithId(this.getCardUniqueId(color, value), card.id);
                }
            },
            notif_addCardToSentence: function (notif) {
                console.log('notifications addCardToSentence');
                stateName = this.currentState;
                console.log(notif.args);
                // Play a card on the table
                var card = notif.args.card;
                if (notif.args.choice != null) {
                     var rotation = notif.args.choice;
                } else {
                     var rotation = 1;
                }
                //?????
                this.playCardOnTable(card, 'current_sentence', rotation, notif.args.player_id);
                
                this.hookupCardsOnLoad();

                console.log('state: '+ stateName);
                

                //  If we are giving cards and this player is still active, make cards in hand that are no longer valid invisible or unselectable
               
                switch (stateName) {

                    case 'giveCards':
                        var playedCardType = notif.args.color;

                        if (this.isCurrentPlayerActive()) {
                            this.hideCardsOfType(playedCardType);
                        }
                        // mark contribution
                        card_icon_id = 'role_icon_p'+ notif.args.player_id;
                        dojo.removeClass(card_icon_id, 'role_icon_0');
                        dojo.addClass(card_icon_id, 'role_icon_'+playedCardType);
                        
                        require(["dojo/html"], function(html){
                            html.set(card_icon_id, '');
                        });


                        break;

                }
                
            },
            
            notif_updateScore: function (notif) {
                console.log('notifications updateScore');
                stateName = this.currentState;
                console.log(notif.args);

                this.scoreCtrl[ notif.args.player_id ].setValue( notif.args.score );
                 // also display guess
                role_icon_id = 'role_icon_p'+ notif.args.player_id;
                require(["dojo/html"], function(html){
                    html.set(role_icon_id, notif.args.choice);
                });
            }


            // TODO: from this point and below, you can write your game notifications handling methods

            /*
            Example:
            
            notif_cardPlayed: function( notif )
            {
                console.log( 'notif_cardPlayed' );
                console.log( notif );
                
                // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
                
                // TODO: play the card in the user interface.
            },    
            
            */
        });
    });


