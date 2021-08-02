<?php
$pdo = new PDO('mysql:dbname=test_base_task;host=mysql', 'root', 'secret', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);


// take initial group of user choise

$inital = $_GET['x'] ?? 0;


// Main func: produce group navs and counts

function display_stuff($parent_group=0) { 

	global $pdo; 

	global $inital;

	// taking group path in tree
	// add 0 and init element to array (kind of lame need to add to get_path function)
	$full_path_arr = array_merge(array(0), get_path($inital), array($inital));

	// retrieve children that belong to full_path_arr only

    if (in_array($parent_group, $full_path_arr)) {

    	// get group name and id 

    	$chunk = $pdo->query('SELECT name,id FROM groups WHERE id_parent="'.$parent_group.'"');

	    // display each child with count

	    foreach ($chunk as $row) {

	    	// geting all products of the group and its children 

	        $str_of_products_ids = all_prods_of_group($row['id']);

	        // count all products of the group and its children

			$prod_count_obj = $pdo->query(' SELECT count(*) FROM products WHERE id_group in ('.$str_of_products_ids.') ');

			$prod_count = $prod_count_obj->fetch(PDO::FETCH_COLUMN);

			// printing navs

			$color = 0;
			if ($inital == $row['id']) {
				$color = "style='color:limegreen'"; 
			}; 

	        echo "<ul>";
	        echo "<li>"."<a ".$color." href='?x=".$row['id']."'>".$row['name']."&nbsp;"."(".$prod_count.")"."</a>"."</li>";

	        // call this function again to display this 
	        // child's children 

	        display_stuff($row['id']);

		};
	};

	echo "</ul>";

} 

// Produce product list of particular group with children

function show_products($group_id) {

	global $pdo; 

	$group_prods = all_prods_of_group($group_id);

	if ($group_id == 0) {

		$prod_list = $pdo->query(' SELECT name FROM products ');
		
	} else {

		$prod_list = $pdo->query(' SELECT name FROM products WHERE id_group in ('.$group_prods.')');

	}

	foreach ($prod_list as $row) {

		echo "<li>".$row['name']."</li>";

	}

}


// Produce path from group to the top of hierarchy

function get_path($node) { 

    global $pdo; 

    // if no node supplied or node=0 to not bother db
    // cos fethcing of empty pdo.object returns 'false' 
    // then treating it as array cos error "Trying to access array offset on value of type bool"

    if (!$node) {

		$path = array(); 
    	return $path;

    };


    // look up the parent of this node 

    $parent_obj = $pdo->query('SELECT id_parent FROM groups WHERE id="'.$node.'"'); 

    $row = $parent_obj->fetch(); 

    // gonna save the path in this array
    $path = array();


    // only continue if this $node isn't the root node (that's the node with id_parent=0) 

    if ($row['id_parent']) { 

        // the last part of the path to $node, is the name of the parent of $node 

        $path[] = $row['id_parent']; 

        // we should add the path to the parent of this node to the path - recursion warning

        $path = array_merge(get_path($row['id_parent']), $path); 

    }


    return $path; 

} 


// Produce string of products of particular group and of all child groups
// in such format eg. 3,4,5,7 (ids of products)
// NOTE it will be much better to add columns with such values to db, products count as well. And then just retrive (TODO)

function all_prods_of_group($group) { 

			global $pdo; 

	        // getting all children ids of current group (https://stackoverflow.com/questions/20215744/how-to-create-a-mysql-hierarchical-recursive-query)

	        $chunk2 = $pdo->query('with recursive cte (id) as (
	        						select id 
	        						from groups 
	        						where id_parent ="'.$group.'" 
	        						union all 
									select     p.id 
									from       groups p 
									inner join cte
									          on p.id_parent = cte.id
									)
									select * from cte '); 

	        // add id of current group for proper count

	        $arr_of_children_ids = array_merge($chunk2->fetchAll(PDO::FETCH_COLUMN),array($group));

	        $result2 = implode(',',$arr_of_children_ids);

	        return $result2;


}

?>



<style>
* {
  box-sizing: border-box;
}

/* Create two equal columns that floats next to each other */
.column {
  float: left;
  width: 50%;
  padding: 10px;
  height: 450px; /* Should be removed. Only for demonstration */
}

/* Clear floats after the columns */
.row:after {
  content: "";
  display: table;
  clear: both;
}

a:active {
  color: green;
  background-color: transparent;
  text-decoration: underline;
}

/* Create box for Zadanie 2 */
  div.container {
    width: 220px;
    height: 170px;
    margin: 10px 50px 10px 10px;
    background: lightgreen;
    border: 2px groove;
    float: left;
  }
</style>
</head>
<body>

<h2>Задание 1</h2>

<div class="row">
  <div class="column" style="background-color:#F5F5F5;">
  	<p><a href="/">Все товары</a></p>

		<?php

			// 0 
			display_stuff();

		?>

  </div>
  <div class="column" style="background-color:#E8E8E8;">
<ul style="list-style: none;">

		<?php

			show_products($inital);

		?>
</ul>

  </div>
</div>
<br>
<br>
<br>
=============================================================================
<h2>Задание 2</h2>
<p id="coords"></p>
<div class="container"></div>
</body>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js">
</script>
<script>

(function ( $ ) {
 
    $.fn.trackCoords = function( options ) {
 
        // This is the easiest way to have default options.
        var settings = $.extend({
            // These are the defaults.
            checkInterval: "30",
            sendInterval: "3000"
        }, options );

        // var for initialization
        var mousePosition = 'mouse is not in area, time - ';
        var listToStoreData = [];
 
		this.mousemove(function(e) {

			const selector = document.getElementById("coords");

	    	// const inPointTime = new Date().getTime();
	        // mousePosition = {'x': e.pageX, 'y': e.pageY};
	        mousePosition = "Coords: X: "+e.clientY+" Y: "+e.clientX+" Time since Jan 1, 1970: ";


	    }); 

        setInterval(function () {
	        // do something with mousePosition
	        const selector = document.getElementById("coords");

		    const inPointTime = new Date().getTime();

		    dataToRec = mousePosition+' '+inPointTime

		    selector.textContent=dataToRec;

		    listToStoreData.push(dataToRec);

		    // window.console && console.log(inPointTime);

		    // window.console && console.log(listToStoreData);

	    }, settings.checkInterval);


	    setInterval(function () {
	        // do something with mousePosition

	    	$.post( settings.url, { 'val': listToStoreData },
		      function( data ) {
		          window.console && console.log(data);

		      });

		    window.console && console.log(listToStoreData);	
		    // empty list of coords
		    listToStoreData = [];


	    }, settings.sendInterval);
 
    };
 
}( jQuery ));

$(document).ready(function(){
	$('div.container').trackCoords({
		checkInterval: "3000",
	    sendInterval: "10000",
	    url: "/save.php"
	});
});


</script>





