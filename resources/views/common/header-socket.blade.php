<script>
/**
 * register webstocket configuration
 */
ns.websocket                =   {
    key: `{{ env( 'PUSHER_APP_KEY' ) }}`,
    port: `{{ env( 'NS_SOCKET_PORT' ) }}`,
    host: `{{ env( 'NS_SOCKET_DOMAIN', env( 'SESSION_DOMAIN' ) ) }}`,
    enabled: <?php echo env( 'NS_SOCKET_ENABLED' ) ? 'true' : 'false' ;?>,
    secured: <?php echo env( 'NS_SOCKET_SECURED', false ) ? 'true' : 'false';?> 
}
</script>