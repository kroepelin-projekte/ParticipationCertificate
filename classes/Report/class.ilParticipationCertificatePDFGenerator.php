<?php

use Mpdf\Mpdf;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Mpdf\MpdfException;
use JetBrains\PhpStorm\NoReturn;

/**
 * Class ilParticipationCertificatePDFGenerator
 *
 * @ilCtrl_isCalledBy ilParticipationCertificatePDFGenerator: ilParticipationCertificateGUI, ilParticipationCertificateTwigParser
 */
class ilParticipationCertificatePDFGenerator
{
    const CMD_PDF = 'generatePDF';

    protected ilTemplate|ilGlobalTemplateInterface $tpl;

    protected ilCtrlInterface $ctrl;

    public string $temp;

    protected ilParticipationCertificatePlugin $pl;


    public function __construct()
    {
        global $DIC, $tempFile, $tempCount;
        $this->tpl = $DIC->ui()->mainTemplate();
        $this->ctrl = $DIC->ctrl();
        $this->pl = ilParticipationCertificatePlugin::getInstance();

        if ($tempCount == 0) {
            $tempFile = $this->temp = ilFileUtils::ilTempnam();
            $tempCount++;
        }
    }


    public function executeCommand(): void
    {
        $cmd = $this->ctrl->getCmd();
        switch ($cmd) {
            default:
                $cmd = $this->ctrl->getCmd(self::CMD_PDF);
                $this->{$cmd}();
                break;
        }
    }

    /**
     * @throws CrossReferenceException
     * @throws PdfTypeException
     * @throws MpdfException
     * @throws PdfParserException
     */
    #[NoReturn]
    public function generatePDF(
        array $renderedCertificates,
        bool $printIsAsynchronous = false
    ): void {

        require_once __DIR__ . '/../../vendor/autoload.php';

        $mpdf = new Mpdf([
            'tempDir' => '/tmp/mpdf',
            'default_font' => 'dejavusans'
        ]);

        $css = file_get_contents(
            './' . ilParticipationCertificatePlugin::PLUGIN_DIRECTORY .
            '/templates/report/Teilnahmebescheinigung.css'
        );

        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        foreach ($renderedCertificates as $index => $rendered) {
            if ($index > 0) {
                $mpdf->AddPage();
            }

            $mpdf->WriteHTML($rendered, \Mpdf\HTMLParserMode::HTML_BODY);
        }

        if ($printIsAsynchronous) {
            $pdfString = $mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);

            echo json_encode([
                'success' => true,
                'pdf_base64' => base64_encode($pdfString)
            ]);
            exit;
        }

        $mpdf->Output($this->pl->txt('plugin') . '.pdf', 'D');
        exit;
    }
}