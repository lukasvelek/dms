<?php

namespace DMS\Components\DocumentReports;

interface IGeneratable {
    function generate(?string $filename = null): array|bool;
}

?>