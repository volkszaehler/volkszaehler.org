<?php
/**
 * Simple test for jquery, ajax and JSON padding
 *
 * @package tests
 * @copyright Copyright (c) 2010, The volkszaehler.org project
 * @license http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author Steffen Vogel <info@steffenvogel.de>
 */
/*
 * This file is part of volkzaehler.org
 *
 * volkzaehler.org is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * volkzaehler.org is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with volkszaehler.org. If not, see <http://www.gnu.org/licenses/>.
 */
?>

<?= '<?xml version="1.0"' ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>volkszaehler.org - ajax test</title>
<script src="../../frontend/javascript/jquery-1.4.2.min.js" type="text/javascript"></script>
<script src="../../frontend/javascript/jstree/jquery.jstree.js" type="text/javascript"></script>

<script type="text/javascript">
$(document).ready(function() {
	$.getJSON('../../backend/group.json?operation=get&recursive=1', function(data) {
		$('#tree').jstree({
			'plugins' : [ 'themes', 'ui', ],
			'types' : {
				'max_children' : -2,
				'max_depth' : -2,
				'valid_children' : [ 'group' ],
				'types' : {
					'group' : {
						'icon' : {
							'image' : 'http://static.jstree.com/v.1.0rc/_docs/_drive.png'
						},
						'valid_children' : [ 'group', 'channel' ]
					},
					'channel' : {
						'icon' : {
							'image' : 'url'
						},
						'valid_children' : 'none'
					}
				}
			}
		});

		for (var i in data.groups) {
			self.addNode(data.groups[i], $('#tree'));
		}
	});
});

function addNode(node, parent) {
	$('#tree').jstree.create_node(parent, 'after', { 'data' : node.name, 'attr' : { 'state' : open, 'uuid' : node.uuid, 'rel' : 'group' } });

	for (var i in node.children) {
		self.addNode(node.children[i], $("*[uuid='" + node.uuid + "']"));
	}

	for (var i in node.channels) {
		$('#tree').jstree.create_node($("*[uuid='" + node.uuid + "']"), 'after', { 'data' : node.channels[i], 'attr' : { 'uuid' : node.uuid, 'rel' : 'channel' } });
	}
}

function test() {}


</script>
</head>
<body>
	<div id="tree" style="width: 500px; height: 300px;">
	</div>
</body>
</html>
