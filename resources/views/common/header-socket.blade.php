<script>
/**
 * register webstocket configuration
 */
ns.websocket                =   {
    key: `{{ env( 'PUSHER_APP_KEY' ) }}`,
    port: `{{ env( 'NS_SOCKET_PORT' ) }}`,
    enabled: <?php echo env( 'NS_SOCKET_ENABLED' ) ? 'true' : 'false' ;?> 
}
</script>