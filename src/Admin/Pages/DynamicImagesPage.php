<?php

namespace Snap\Admin\Pages;

use WP_List_Table;

class DynamicImagesPage
{
    /**
     * @var \WP_List_Table
     */
    private $table;

    /**
     * DynamicImagesPage constructor.
     *
     * @param \WP_List_Table $table The table instance to display on this page
     */
    public function __construct(WP_List_Table $table)
    {
        $this->table = $table;
    }

    /**
     * Render the page.
     */
    public function render(): void
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Dynamic Image Management</h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <form method="post">
                                <?php
                                $this->table->prepare_items();
                                $this->table->display();
                                ?>
                            </form>
                        </div>
                    </div>
                </div>
                <br class="clear">
            </div>
        </div>
        <?php
    }
}
