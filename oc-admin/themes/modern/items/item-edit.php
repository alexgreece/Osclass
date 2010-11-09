<script type="text/javascript" src="<?php echo WEB_PATH;?>/oc-includes/js/tabber-minimized.js"></script>
<link type="text/css" href="<?php echo WEB_PATH;?>/oc-includes/css/tabs.css" media="screen" rel="stylesheet" />
<script type="text/javascript">
	document.write('<style type="text/css">.tabber{display:none;}<\/style>');
</script>
<?php ItemForm::location_javascript(); ?>
<div id="content">
    <div id="separator"></div>

    <?php include_once $absolute_path . '/include/backoffice_menu.php'; ?>

    <div id="right_column">
        <div id="home_header" style="margin-left: 40px;"><h2><?php _e('Update your item'); ?></h2></div>
        <div align="center">
            <div id="add_item_form">
                <form action="items.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="item_edit_post" />
                    <input type="hidden" name="id" value="<?php echo $item['pk_i_id'];?>" />
                    <input type="hidden" name="secret" value="<?php echo $item['s_secret'];?>" />

                    <!-- left -->
                    <div class="left column">
                        <h2>
                            <?php _e('General Information'); ?>
                        </h2>

                        <?php ItemForm::category_select($categories, $item); ?>

                        <?php ItemForm::multilanguage_title_description($locales, $item); ?>

                        <div>
                            <h2><?php _e('Price'); ?></h2>
                            <?php ItemForm::price_input_text($item); ?>
                            <?php ItemForm::currency_select($currencies, $item); ?>
                        </div>

                        <div>
                            <script type="text/javascript">
                                var photoIndex = 0;
                                function gebi(id) { return document.getElementById(id); }
                                function ce(name) { return document.createElement(name); }
                                function re(id) {
                                    var e = gebi(id);
                                    e.parentNode.removeChild(e);
                                }
                                function addNewPhoto() {
                                    var id = 'p-' + photoIndex++;

                                    var i = ce('input');
                                    i.setAttribute('type', 'file');
                                    i.setAttribute('name', 'photos[]');

                                    var a = ce('a');
                                    a.style.fontSize = 'x-small';
                                    a.setAttribute('href', '#');
                                    a.setAttribute('divid', id);
                                    a.onclick = function() { re(this.getAttribute('divid')); return false; }
                                    a.appendChild(document.createTextNode('<?php echo __('Remove'); ?>'));

                                    var d = ce('div');
                                    d.setAttribute('id', id);

                                    d.appendChild(i);
                                    d.appendChild(a);

                                    gebi('photos').appendChild(d);
                                }

                                $(document).ready(function() {
                                    $('a.delete').click(function(e) {
                                        e.preventDefault();
                                        var parent = $(this).parent();
                                        $.ajax({
                                            type: 'get',
                                            url: 'items.php',
                                            data: 'action=deleteResource&id='+parent.attr('id')+'&fkid='+parent.attr('fkid')+'&name='+parent.attr('name'),
                                            success: function() {
                                                parent.slideUp(300,function() {
                                                    parent.remove();
                                                });
                                            }
                                        });
                                    });
                                });
                            </script>

                            <?php echo __('Photos'); ?><br />
                            <div id="photos">
                                <?php foreach($resources as $_r) {?>
                                    <div id="<?php echo $_r['pk_i_id'];?>" fkid="<?php echo $_r['fk_i_item_id'];?>" name="<?php echo $_r['s_name'];?>">
                                        <img src="../<?php echo $_r['s_path'];?>" /><a onclick=\"javascript:return confirm('<?php echo __('This action can not be undone. Are you sure you want to continue?'); ?>')\" href="items.php?action=deleteResource&id=<?php echo $_r['pk_i_id'];?>&fkid=<?php echo $_r['fk_i_item_id'];?>&name=<?php echo $_r['s_name'];?>" class="delete"><?php echo __('Delete'); ?></a>
                                    </div>
                                <?php } ?>
                                <div>
                                    <input type="file" name="photos[]" /> (<?php echo __('optional'); ?>)
                                </div>
                            </div>
                            <a style="font-size: small;" href="#" onclick="addNewPhoto(); return false;"><?php echo __('Add new photo'); ?></a>
                        </div>
                    </div>

                        <!-- right -->
                    <div class="right column">
                        <div class="location-post">
                            <!-- location info -->
                            <h2><?php _e('Location'); ?></h2>
                            <dl>
                                <dt><?php _e('Country'); ?></dt>
                                <dd><?php ItemForm::country_select($countries, $item) ; ?></dd>
                                <dt><?php _e('Region'); ?></dt>
                                <dd><?php ItemForm::region_select($regions, $item) ; ?></dd>
                                <dt><?php _e('City'); ?></dt>
                                <dd><?php ItemForm::city_select($cities, $item) ; ?></dd>
                                <dt><?php _e('City area'); ?></dt>
                                <dd><?php ItemForm::city_area_text($item) ; ?></dd>
                                <dt><?php _e('Address'); ?></dt>
                                <dd><?php ItemForm::address_text($item) ; ?></dd>
                            </dl>
                        </div>

                        <?php
                            osc_runHook('item_edit', $item);
                        ?>
                    </div>
                    <div class="clear"></div>
                    <div align="center" style="margin-top: 30px; padding: 20px; background-color: #eee;">
                        <button style="background-color: orange; color: white;" type="button" onclick="window.location='items.php';" ><?php echo __('Cancel'); ?></button>
                        <button style="background-color: orange; color: white;" type="submit"><?php echo __('Update'); ?></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
