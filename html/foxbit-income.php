<?php
declare(strict_types=1);

require_once(($_SERVER["DOCUMENT_ROOT"] ?: __DIR__) . "/Kickback/init.php");

$session = require(\Kickback\SCRIPT_ROOT . "/api/v1/engine/session/verifySession.php");
require("php-components/base-page-pull-active-account-info.php");


use Kickback\Backend\Controllers\LichCardController;
use Kickback\Backend\Models\Response;
use Kickback\Backend\Views\vLichCard;


?>

<!DOCTYPE html>
<html lang="en">


<?php require("php-components/base-page-head.php"); ?>

<body class="bg-body-secondary container p-0">
    
    <?php 
    
    require("php-components/base-page-components.php"); 
    
    require("php-components/ad-carousel.php"); 
    
    ?>

    

    <!--MAIN CONTENT-->
    <main class="container pt-3 bg-body" style="margin-bottom: 56px;">
        <div class="row">
            <div class="col-12 col-xl-9">
                
                
                <?php 
                
                
                $activePageName = "Foxbit PDF BRL Extractor";
                require("php-components/base-page-breadcrumbs.php"); 
                
                ?>
                <div class="card shadow-sm mb-4">
                  <div class="card-body">
                    
                    <div class="mb-3">
                      <label for="fileInput" class="form-label">Upload Foxbit PDF</label>
                      <input type="file" class="form-control" id="fileInput" accept="application/pdf">
                    </div>

                    <button id="downloadCsvBtn" class="btn btn-primary mb-3" disabled>
                      <i class="bi bi-download me-1"></i> Download CSV
                    </button>

                    <div id="output" class="card bg-light p-3 border">
                      <p class="text-muted">No data loaded yet.</p>
                    </div>
                  </div>
                </div>




            </div>
            
            <?php require("php-components/base-page-discord.php"); ?>
        </div>
        <?php require("php-components/base-page-footer.php"); ?>
    </main>

    
    <?php require("php-components/base-page-javascript.php"); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
  <script>
    const regex = /Retirada\s+BRL\s+(\d{1,3}(?:\.\d{3})*,\d{2}).*?(\d{2}\/\d{2}\/\d{2})/g;

    const sumAmounts = (data) => {
      const totalInCents = data.reduce((sum, entry) => {
        const normalized = entry.Amount.replace(/\./g, '').replace(',', '.');
        const amount = parseFloat(normalized);
        return sum + Math.round(amount * 100);
      }, 0);
      return totalInCents / 100;
    };

    const parsePDF = async (pdfData) => {
      const pdf = await pdfjsLib.getDocument({ data: pdfData }).promise;
      let allText = '';
      for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const content = await page.getTextContent();
        const strings = content.items.map(item => item.str).join(' ');
        allText += strings + '\n';
      }
      return allText;
    };

    const extractData = (text) => {
      let match;
      const results = [];
      while ((match = regex.exec(text)) !== null) {
        results.push({
          Amount: match[1],
          Date: match[2]
        });
      }
      return results;
    };

    
    const generateCSV = (data, total) => {
      let csv = 'Amount,Date\n';
      data.forEach(row => {
        csv += `"${row.Amount}","${row.Date}"\n`;
      });
      csv += `"Total: ${total.toFixed(2)}",`;
      return csv;
    };

    const downloadCSV = () => {
      const total = sumAmounts(parsedData);
      const csvContent = generateCSV(parsedData, total);
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = 'foxbit-withdrawals.csv';
      link.click();
    };

    document.getElementById('fileInput').addEventListener('change', async (e) => {
      const file = e.target.files[0];
      if (!file) return;

      const arrayBuffer = await file.arrayBuffer();
      const text = await parsePDF(arrayBuffer);
      parsedData = extractData(text);
      const total = sumAmounts(parsedData);

      document.getElementById('downloadCsvBtn').disabled = parsedData.length === 0;

      const output = document.getElementById('output');
      output.innerHTML = `
  <h5 class="mb-3">Found ${parsedData.length} entries</h5>

  <div class="mb-4" style="max-height: 200px; overflow-y: auto;">
    <pre class="bg-white p-2 border rounded small">${JSON.stringify(parsedData, null, 2)}</pre>
  </div>

  <div class="table-responsive mb-4">
    <table class="table table-sm table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Amount (BRL)</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        ${parsedData.map(row => `
          <tr>
            <td>${row.Amount}</td>
            <td>${row.Date}</td>
          </tr>`).join('')}
        <tr class="fw-bold table-warning">
          <td>Total: R$ ${total.toFixed(2)}</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
`;

    });

    document.getElementById('downloadCsvBtn').addEventListener('click', downloadCSV);
  </script>
</body>

</html>