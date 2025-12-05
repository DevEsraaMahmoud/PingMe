# Broadcasting Debugging Guide

## Quick Checklist

1. **Is Reverb Server Running?**
   ```bash
   php artisan reverb:start
   ```
   You should see: `Reverb server started on 127.0.0.1:8080`

2. **Check Browser Console**
   - Open DevTools (F12)
   - Look for Echo connection messages
   - Should see: `✅ Echo connected to WebSocket server`
   - Should see: `✅ Successfully subscribed to conversation channel: X`

3. **Check Laravel Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   Look for: `Message broadcasted` entries

4. **Verify Environment Variables**
   ```bash
   php artisan tinker
   ```
   Then run:
   ```php
   config('broadcasting.default'); // Should return 'reverb'
   env('REVERB_APP_KEY'); // Should return 'my-app-key'
   ```

## Common Issues

### Issue: "Echo is not initialized"
**Solution:** Make sure `resources/js/bootstrap/echo.js` is imported in `app.js`

### Issue: "Channel subscription error: 403"
**Solution:** Check `routes/channels.php` - user must be a participant

### Issue: WebSocket connection fails
**Solution:** 
1. Make sure Reverb server is running
2. Check firewall isn't blocking port 8080
3. Verify VITE_REVERB_* variables match REVERB_* variables

### Issue: Messages not appearing
**Solution:**
1. Check browser console for errors
2. Verify event is being broadcast (check Laravel logs)
3. Make sure channel subscription succeeded
4. Check event name matches (should be `.MessageSent`)

## Manual Test

1. Open two browser windows
2. Login as different users
3. Open same conversation in both
4. Check console in both windows
5. Send message from Window 1
6. Check Window 2 console for broadcast message



