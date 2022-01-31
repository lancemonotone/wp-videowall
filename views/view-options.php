<script type="text/javascript">var __namespace = '<?php echo $namespace; ?>';</script>
<div class="wrap">

    <h2><?php echo $page_title; ?></h2>
        
    <?php if( isset( $_GET['message'] ) ): ?>
        <div id="message" class="updated below-h2"><p>Options successfully updated!</p></div>
    <?php endif; ?>

    <form action="" method="post" id="<?php echo $namespace; ?>-form">
        <?php wp_nonce_field( $namespace . "-update-options" ); ?>
        
        <p>
            <label><input type="text" name="data[option_1]" value="<?php echo $this->get_option( 'option_1' ); ?>" /> This is an example of an option.</label> 
        </p>
        <p>
            <label><input type="text" name="data[option_2]" value="<?php echo $this->get_option( 'option_2' ); ?>" /> This is an example of an option.</label> 
        </p>
        <p>
            <label><input type="text" name="data[option_3]" value="<?php echo $this->get_option( 'option_3' ); ?>" /> This is an example of an option.</label> 
        </p>
        <p>
            <label><input type="text" name="data[option_4]" value="<?php echo $this->get_option( 'option_4' ); ?>" /> This is an example of an option.</label> 
        </p>
        
        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php _e( "Save Changes", $namespace ) ?>" />
        </p>
    </form>
    
</div>