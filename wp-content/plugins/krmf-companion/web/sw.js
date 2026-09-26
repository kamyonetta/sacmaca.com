/* Cache static assets only. Never cache API data, credentials or login pages. */
const CACHE='krmf-static-v1';
async function cacheShell(response,cache){
  if(response.ok && response.headers.get('X-KRMF-Shell')==='1'){
    const html=(await response.clone().text()).replace(/<script id="krmf-session">[\s\S]*?<\/script>/,'');
    await cache.put('krmf-offline-shell',new Response(html,{headers:{'Content-Type':'text/html'}}));
  }
}
self.addEventListener('message',event=>{
  if(event.data?.type!=='KRMF_PRIME' || !event.source?.url?.startsWith(self.registration.scope))return;
  event.waitUntil((async()=>{
    const cache=await caches.open(CACHE);
    await cacheShell(await fetch(self.registration.scope,{credentials:'same-origin',cache:'no-store'}),cache);
    for(const path of event.data.assets??[]){
      const url=new URL(path);
      if(url.origin===location.origin && /\/(?:assets|art)\/[^/]+\.(?:js|css|png|ttf)$/.test(url.pathname)){
        const response=await fetch(url);if(response.ok)await cache.put(url,response);
      }
    }
  })());
});
self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',event=>event.waitUntil(self.clients.claim()));
self.addEventListener('fetch',event=>{
  const url=new URL(event.request.url);
  if(event.request.method!=='GET' || url.origin!==location.origin)return;
  // Do not intercept WordPress, APIs or other pages on this website.
  if(event.request.mode==='navigate'){
    if(url.pathname!==new URL(self.registration.scope).pathname)return;
    event.respondWith((async()=>{
      const cache=await caches.open(CACHE);
      try{
        const response=await fetch(event.request);
        await cacheShell(response,cache);
        return response;
      }catch(error){const saved=await cache.match('krmf-offline-shell');if(saved)return saved;throw error;}
    })());return;
  }
  if(!/\/(?:assets|art)\/[^/]+\.(?:js|css|png|ttf)$/.test(url.pathname))return;
  event.respondWith(caches.open(CACHE).then(async cache=>{
    const cached=await cache.match(event.request);if(cached)return cached;
    const response=await fetch(event.request);if(response.ok && response.type==='basic')await cache.put(event.request,response.clone());return response;
  }));
});
