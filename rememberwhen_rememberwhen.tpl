{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- Hearts implementation : © Gregory Isabelli <gisabelli@boardgamearena.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    hearts_hearts.tpl
    
    This is the HTML template of your game.
    
    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.
    
    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format
    
    See your "view" PHP file to check how to set variables and control blocks
-->

<!-- <div id="playertables"> -->

    <!-- BEGIN player -->
<!--    <div class="playertable whiteblock playertable_{DIR}">
        <div class="playertablename" style="color:#{PLAYER_COLOR}">
            {PLAYER_NAME}
        </div>
        <div class="playertablecard" id="playertablecard_{PLAYER_ID}">
        </div>
    </div> -->
    <!-- END player -->

<!--</div> -->

<div id="sentence_board" class="floatL whiteblock ">
	<ul class="tab">
	  <li><a href="#" class="tablinks" id="top_tab" onclick="showSentence(event, 'top_sentence')">Top Memory</a></li>
	  <li><a href="#" class="tablinks active" id="current_tab" onclick="showSentence(event, 'current_sentence')">Current Memory</a></li>
	</ul>
    <div id="current_sentence" class="sentenceboard whiteblock tabcontent" style=""> 
		<div class="spot" id="spot_1"></div><div class="spot" id="spot_2"></div><div class="spot" id="spot_3"></div><div class="spot" id="spot_4"></div>
		<div class="spot" id="spot_5"></div><div class="spot" id="spot_6"></div><div class="spot" id="spot_7"></div><div class="spot" id="spot_8"></div>

    </div> 
	<div id="top_sentence" class="sentenceboard whiteblock tabcontent"> 
		<div class="spot spot_1" id="top_spot_1"></div><div class="spot spot_2" id="top_spot_2"></div><div class="spot spot_3" id="top_spot_3"></div><div class="spot spot_4" id="top_spot_4">4</div>
		<div class="spot spot_5" id="top_spot_5"></div><div class="spot spot_6" id="top_spot_6"></div><div class="spot spot_7" id="top_spot_7"></div><div class="spot spot_8" id="top_spot_8">8</div>

    </div> 

</div>

<div id="myhand_wrap" class="whiteblock hand">
    <h3>{MY_HAND}</h3>
    <div id="myhand">
    </div>
</div>






<script type="text/javascript">

                    
function showSentence(evt, cityName) {
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the link that opened the tab
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}
 
var jstpl_cardontable = '<div class="cardontable spot" id="${player_id}" type="${type}" style="background-position:-${x}px -${y}px"><div class="text_1"><span class="text">${text_1}</span></div><div class="text_2"><span class="text">${text_2}</span></div><div class="text_3"><span class="text">${text_3}</span></div><div class="text_4"><span class="text">${text_4}</span></div></div>';
						
var jstpl_disc='<div class="disc disccolor_${color}" id="disc_${xy}"></div>';

var jstpl_role = '<div id="role_icon_p${player}" class="role_icon role_icon_${color}">${role}</div>';

var jstpl_card_table = '<div class="divTable" id="tip_${id}">\
	<div class="divTableBody tip_${pos}">\
		<div id="row_1" class="divTableRow">\
			<div class="divTableCell">&nbsp;1</div>\
			<div class="divTableCell">&nbsp;${text_1}</div>\
		</div>\
		<div class="divTableRow">\
			<div class="divTableCell">&nbsp;2</div>\
			<div class="divTableCell">&nbsp;${text_2}</div>\
		</div>\
		<div class="divTableRow">\
			<div class="divTableCell">&nbsp;3</div>\
			<div class="divTableCell">&nbsp;${text_3}</div>\
		</div>\
		<div class="divTableRow">\
			<div class="divTableCell">&nbsp;4</div>\
			<div class="divTableCell">&nbsp;${text_4}</div>\
		</div>\
	</div>\
</div>';
    
</script>  




{OVERALL_GAME_FOOTER}
