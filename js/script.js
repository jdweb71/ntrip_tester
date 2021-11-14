/*****
Cette oeuvre est mise à disposition selon les termes de la Licence Creative Commons Attribution 
- Pas d'Utilisation Commerciale 
- Pas de Modification 
4.0 International
http://creativecommons.org/licenses/by-nc-nd/4.0/
09/11/2021 Julian Desfetes
******/

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
})

function get_time() {
    var d = new Date();
    var h = ('0' + d.getHours()).slice(-2);
    var m = ('0' + d.getMinutes()).slice(-2);
    var s = ('0' + d.getSeconds()).slice(-2);
    return h + '' + m + '' + s;
}

document.querySelector('#time').value = get_time();



var options = {
    enableHighAccuracy: true,
    timeout: 5000,
    maximumAge: 0
};

function success(pos) {
    var crd = pos.coords;
    document.querySelector('#lat').value = crd.latitude;
    document.querySelector('#lon').value = crd.longitude;
}

function error(err) {
    console.warn(`ERREUR (${err.code}): ${err.message}`);
}

async function get_request(url) {
    try {
        var response = await fetch(url);

        try {
            return await response.json();
        } catch (e) {
            return response;
        }

    }
    catch (err) {
        return err;
    }
}

navigator.geolocation.getCurrentPosition(success, error, options);

function create_elem(tag, into, attr = '{}') {
    attr = JSON.parse(attr);
    var el = document.createElement(tag);
    if (attr.text) {
        try {
            el.innerHTML = decodeURI(attr.text);
        }
        catch (e) {
            el.innerHTML = attr.text;
        }
        delete attr.text;
    }

    if (attr.id) {
        el.id = attr.id;
        delete attr.id;
    }
    if (attr.src) {
        el.src = attr.src;
        delete attr.src;
    }
    Object.keys(attr).forEach(function (key) {
        var att = document.createAttribute(key);

        try {
            att.value = decodeURI(attr[key]).replace(/(<([^>]+)>)/gi, "");
        }
        catch (e) {
            att.value = attr[key];
        }
        el.setAttributeNode(att);
    });
    into.appendChild(el);
    return el;
}

getmp = document.querySelector('#getmountpoint');
mp = document.querySelector('#mp');

getmp.addEventListener('click', async function (event) {
    event.preventDefault();
    mp.value = '';
    mp.dataset.format = '';
    console.log('get mountpoint list');
    caster = document.querySelector('#caster').value;
    port = document.querySelector('#port').value;
    if ((caster !== '') && (port !== '')) {
        jsonmplist = await get_request("ajax.php?curl=get_mp&caster=" + caster + ":" + port);

        mplist = document.querySelector('#mplist');
        Object.values(jsonmplist).forEach(element => {
            mplist.innerHTML += '<option data-format="' + element.format + '" value="' + element.name + '">';

        });

    }
});


mp.addEventListener('change', async function () {
    if (mp.value !== '') {
        var format = 'unknow';
        jsonmplist = await get_request("ajax.php?curl=get_mp&caster=" + caster + ":" + port);
        Object.values(jsonmplist).forEach(element => { if (element.name == mp.value) { format = element.format; } });
        mp.dataset.format = format;
    }
});


form = document.querySelector('#form');

form.addEventListener('submit', async function (event) {
    event.preventDefault();
    document.querySelector("#mess").innerHTML = "";
    document.querySelector("#textOutput").innerHTML = "";
    document.querySelector("#outputFormat").innerHTML = "";
    start = document.querySelector('#start');
    start.setAttribute('disabled', 'true');
    stop = document.querySelector('#stop');
    stop.removeAttribute('disabled');
    logcontainer = document.querySelector("#logdata");
    if (logcontainer.classList.contains('invisible')) {
        logcontainer.classList.toggle('invisible');
    }

    secure = JSON.stringify(await get_request("ajax.php?action=gen_light_tk"));
    secure = JSON.parse(secure);

    await get_request("ajax.php?action=init_session&tk=" + secure.tk);

    var forxsata = new FormData(form);
    forxsata.append('tk', secure.tk);
    query = new URLSearchParams(forxsata).toString();

    console.log('click to start');
    get_request("ajax.php?" + query);
    console.log('started ');
    progress_loop = setInterval(async function () {
        console.log('get progress');
        progress = await get_request("ajax.php?curl=progress&tk=" + secure.tk);
        jsonprogress = JSON.stringify(progress);
        //console.log(progress);
        try {
            result = JSON.parse(jsonprogress);
            document.querySelector("#outputFormat").innerHTML = 'Caster output format: ' + document.querySelector("#mp").dataset.format;
            tbl = document.querySelector("#mess");


            if (result.status == 'progress') {

                tbl.innerHTML = '';
                tr = create_elem('tr', tbl);
                create_elem('td', tr, '{"text":"' + result.currentSpeed + ' b/s"}');
                create_elem('td', tr, '{"text":"' + result.downloaded + '"}');
                create_elem('td', tr, '{"text":"' + result.time + '"}');

            } else if (result.status == 'finish') {

                tbl.innerHTML = '';
            } else {
                tbl.innerHTML = '';
                tr = create_elem('tr', tbl);
                create_elem('td', tr, '{"text":"---"}');
                create_elem('td', tr, '{"text":"---"}');
                create_elem('td', tr, '{"text":"--:--"}');

            }


            output = await get_request('ajax.php?file=' + result.outputFile + '&dl=' + result.downloaded);
            text = JSON.stringify(output);
            file = JSON.parse(text);
            document.querySelector("#textOutput").style.color = file.color;
            if (typeof file.txt !== 'undefined') {
                document.querySelector("#textOutput").innerHTML = file.txt;
            }


        } catch (e) { }
    }, 500);


    stop.addEventListener('click', function (event) {
        event.preventDefault();
        logcontainer = document.querySelector("#logdata");
        if (!logcontainer.classList.contains('invisible')) {
            logcontainer.classList.toggle('invisible');
        }
        console.log('stop click');

        stop.setAttribute('disabled', 'true');
        start.removeAttribute('disabled');
        clearInterval(progress_loop);
        get_request("ajax.php?curl=cancel&tk=" + secure.tk);
        console.log('stopped');

        document.querySelector("#mess").innerHTML = "";
        document.querySelector("#textOutput").innerHTML = "";
        document.querySelector("#outputFormat").innerHTML = "";
    });

});

  document.querySelector('#pass_show_hide').addEventListener('click',function(){
    var x = document.getElementById("pass");
    var show_eye = document.getElementById("show_eye");
    var hide_eye = document.getElementById("hide_eye");
    hide_eye.classList.remove("d-none");
    if (x.type === "password") {
      x.type = "text";
      show_eye.style.display = "none";
      hide_eye.style.display = "block";
    } else {
      x.type = "password";
      show_eye.style.display = "block";
      hide_eye.style.display = "none";
    }
  });
