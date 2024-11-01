<div id="zakeke-configurator-container">
    <iframe id="zakeke-configurator-frame" src="<?php echo ZAKEKE_CONFIGURATOR_BASE_URL . '/Configurator/index.html' ?>" frameBorder="0" allowfullscreen></iframe>
    <script type="application/javascript">
        window.zakekeConfiguratorConfig = <?php echo json_encode( $final_atts ); ?>;
    </script>
</div>