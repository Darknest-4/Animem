<!DOCTYPE html>
<html>
    <body>
        <video controls="" preload="auto" id="_video"></video>
    </body>
</html>

<script>
function createObjectURL(object) {
    return (window.URL) ? window.URL.createObjectURL(object) : window.webkitURL.createObjectURL(object);
}

async function display(videoStream){
    var video = document.getElementById('_video');
    let blob = await fetch(videoStream).then(r => r.blob());
    var videoUrl=createObjectURL(blob);
    video.src = videoUrl;
}

display('https://cdn.animem.org/anime/136/1536');
</script>