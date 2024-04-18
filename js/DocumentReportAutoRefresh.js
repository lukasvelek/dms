const sleep_length = 5000; // 5s

function sleep(ms) {
    return new Promise(r => setTimeout(r, ms));
}

async function run() {
    loadData();
    await sleep(sleep_length);
    await run();
}

function loadData() {
    $.get("app/ajax/DocumentReports.php", {
        action: "loadProgress"
    },
    async function(data) {
        $('#tablebuilder-table').html(data);
    });
}

async function startup() {
    await sleep(sleep_length);
    await run();
}
