<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GenerateReportTool extends Tool
{
    protected string $name = 'generate_report';

    protected string $description = 'Generate a temporary downloadable PDF report from a self-contained HTML template with a predefined store-branded header/footer. The AI supplies already-computed data and may compose sections as text, KPI cards, tables, bar charts, or pie charts. This tool does not query data, execute SQL, run JavaScript, or fetch external assets.';

    public function schema( JsonSchema $schema ): array
    {
        $dataPoint = $schema->object( [
            'label' => $schema->string()->description( 'Display label for the chart segment/bar or KPI item.' )->required(),
            'value' => $schema->number()->description( 'Numeric value.' )->required(),
            'detail' => $schema->string()->description( 'Optional supporting text for KPI items.' )->nullable(),
            'color' => $schema->string()->description( 'Optional CSS color such as #2563eb.' )->nullable(),
        ] );

        $section = $schema->object( [
            'type' => $schema->string()
                ->description( 'Section type. Supported values: text, kpi_grid, table, bar_chart, pie_chart, page_break.' )
                ->required(),
            'title' => $schema->string()->description( 'Optional section title.' )->nullable(),
            'description' => $schema->string()->description( 'Optional section description.' )->nullable(),
            'content' => $schema->string()->description( 'Text content for text sections. Plain text only; HTML is escaped.' )->nullable(),
            'columns' => $schema->array()->items( $schema->string() )->description( 'Table column labels. Required for table sections.' )->nullable(),
            'rows' => $schema->array()->items( $schema->object() )->description( 'Table rows as objects keyed by the column labels, or arrays in the same order as columns.' )->nullable(),
            'items' => $schema->array()->items( $dataPoint )->description( 'KPI items or chart data points. Required for kpi_grid, bar_chart, and pie_chart sections.' )->nullable(),
        ] );

        return [
            'title' => $schema->string()->description( 'Report title.' )->required(),
            'subtitle' => $schema->string()->description( 'Optional subtitle shown below the report title.' )->nullable(),
            'period_label' => $schema->string()->description( 'Optional report period label, e.g. "June 2026" or "2026-06-01 to 2026-06-17".' )->nullable(),
            'prepared_by' => $schema->string()->description( 'Optional author/preparer label.' )->nullable(),
            'filename' => $schema->string()->description( 'Optional file name without extension. The tool will sanitize it and create matching .pdf and .html files.' )->nullable(),
            'expires_in_minutes' => $schema->integer()->description( 'Temporary link lifetime in minutes. Defaults to 120, minimum 5, maximum 1440.' )->default( 120 )->min( 5 )->max( 1440 ),
            'sections' => $schema->array()->items( $section )->description( 'Ordered report sections. Supported section types are text, kpi_grid, table, bar_chart, pie_chart, and page_break.' )->required(),
        ];
    }

    public function handle( Request $request ): Response
    {
        try {
            $title = trim( (string) $request->get( 'title' ) );
            $sections = $request->get( 'sections', [] );

            if ( $title === '' ) {
                return Response::error( __( 'The report title is required.' ) );
            }

            if ( ! is_array( $sections ) || empty( $sections ) ) {
                return Response::error( __( 'The report requires at least one section.' ) );
            }

            $expiresInMinutes = max( 5, min( 1440, (int) $request->get( 'expires_in_minutes', 120 ) ) );
            $expiresAt = Carbon::now()->addMinutes( $expiresInMinutes );
            $baseFilename = $this->makeBaseFilename( $request->get( 'filename' ), $title );
            $htmlFilename = $baseFilename . '.html';
            $pdfFilename = $baseFilename . '.pdf';

            $html = $this->renderReport( [
                'title' => $title,
                'subtitle' => $request->get( 'subtitle' ),
                'period_label' => $request->get( 'period_label' ),
                'prepared_by' => $request->get( 'prepared_by' ),
                'sections' => $sections,
            ] );

            Storage::disk( 'ns-temp' )->put( 'mcp-reports/' . $htmlFilename, $html );
            Storage::disk( 'ns-temp' )->put( 'mcp-reports/' . $pdfFilename, $this->renderPdf( $html ) );

            $downloadUrl = URL::temporarySignedRoute(
                'mcp.reports.download',
                $expiresAt,
                ['filename' => $pdfFilename]
            );

            $htmlPreviewUrl = URL::temporarySignedRoute(
                'mcp.reports.download',
                $expiresAt,
                ['filename' => $htmlFilename]
            );

            return Response::json( [
                'status' => 'success',
                'format' => 'pdf',
                'filename' => $pdfFilename,
                'html_filename' => $htmlFilename,
                'download_url' => $downloadUrl,
                'html_preview_url' => $htmlPreviewUrl,
                'expires_at' => $expiresAt->toIso8601String(),
                'capabilities' => [
                    'sections' => ['text', 'kpi_grid', 'table', 'bar_chart', 'pie_chart', 'page_break'],
                    'charts' => ['bar_chart', 'pie_chart'],
                    'output' => 'PDF generated from self-contained HTML with inline CSS and SVG charts. The source HTML is also available as a temporary preview link.',
                ],
                'limitations' => [
                    'The tool does not fetch or compute report data. Use other MCP tools first, then pass summarized data here.',
                    'The PDF renderer is server-side Dompdf, so JavaScript, remote assets, and advanced browser-only CSS are not supported.',
                    'HTML input is escaped; only structured report fields are rendered.',
                    'Chart values must be numeric and greater than or equal to zero.',
                ],
            ] );
        } catch ( \Throwable $e ) {
            return Response::error( $e->getMessage() );
        }
    }

    private function makeBaseFilename( ?string $filename, string $title ): string
    {
        $base = Str::slug( $filename ?: $title ) ?: 'report';

        return $base . '-' . now()->format( 'Ymd-His' ) . '-' . Str::lower( Str::random( 6 ) );
    }

    private function renderPdf( string $html ): string
    {
        $options = new Options;
        $options->set( 'defaultFont', 'DejaVu Sans' );
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'isRemoteEnabled', false );

        $dompdf = new Dompdf( $options );
        $dompdf->setPaper( 'A4', 'portrait' );
        $dompdf->loadHtml( $html, 'UTF-8' );
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * @param array{title: string, subtitle?: string|null, period_label?: string|null, prepared_by?: string|null, sections: array<int, mixed>} $report
     */
    private function renderReport( array $report ): string
    {
        $store = $this->storeDetails();
        $generatedAt = now()->format( 'Y-m-d H:i:s' );
        $sections = collect( $report['sections'] )
            ->map( fn ( $section ) => $this->renderSection( is_array( $section ) ? $section : [] ) )
            ->implode( "\n" );

        return '<!doctype html>' .
            '<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' .
            '<title>' . e( $report['title'] ) . '</title>' .
            '<style>' . $this->stylesheet() . '</style></head><body>' .
            '<main class="report-page">' .
            '<header class="store-header">' .
            '<div><div class="brand-mark">' . e( Str::substr( $store['name'], 0, 1 ) ) . '</div></div>' .
            '<div class="store-copy"><h1>' . e( $store['name'] ) . '</h1>' .
            '<p>' . e( $store['address'] ) . '</p><p>' . e( $store['contact'] ) . '</p></div>' .
            '</header>' .
            '<section class="report-title"><p class="eyebrow">Generated report</p><h2>' . e( $report['title'] ) . '</h2>' .
            $this->optionalParagraph( $report['subtitle'] ?? null, 'subtitle' ) .
            '<div class="meta-row">' .
            $this->metaPill( 'Period', $report['period_label'] ?? 'Not specified' ) .
            $this->metaPill( 'Prepared by', $report['prepared_by'] ?? 'AI assistant' ) .
            $this->metaPill( 'Generated', $generatedAt ) .
            '</div></section>' .
            $sections .
            '<footer class="report-footer"><span>' . e( $store['name'] ) . '</span><span>Generated ' . e( $generatedAt ) . '</span></footer>' .
            '</main></body></html>';
    }

    /**
     * @param array<string, mixed> $section
     */
    private function renderSection( array $section ): string
    {
        $type = (string) ( $section['type'] ?? '' );

        return match ( $type ) {
            'text' => $this->renderTextSection( $section ),
            'kpi_grid' => $this->renderKpiGrid( $section ),
            'table' => $this->renderTable( $section ),
            'bar_chart' => $this->renderChartSection( $section, 'bar' ),
            'pie_chart' => $this->renderChartSection( $section, 'pie' ),
            'page_break' => '<div class="page-break"></div>',
            default => $this->renderUnsupportedSection( $type ),
        };
    }

    /** @param array<string, mixed> $section */
    private function renderTextSection( array $section ): string
    {
        return $this->sectionShell( $section, '<p class="body-copy">' . nl2br( e( (string) ( $section['content'] ?? '' ) ) ) . '</p>' );
    }

    /** @param array<string, mixed> $section */
    private function renderKpiGrid( array $section ): string
    {
        $items = is_array( $section['items'] ?? null ) ? $section['items'] : [];
        $html = collect( $items )->map( function ( $item ) {
            $item = is_array( $item ) ? $item : [];

            return '<article class="kpi-card"><span>' . e( (string) ( $item['label'] ?? 'Metric' ) ) . '</span>' .
                '<strong>' . e( (string) ( $item['value'] ?? '' ) ) . '</strong>' .
                '<small>' . e( (string) ( $item['detail'] ?? '' ) ) . '</small></article>';
        } )->implode( '' );

        return $this->sectionShell( $section, '<div class="kpi-grid">' . $html . '</div>' );
    }

    /** @param array<string, mixed> $section */
    private function renderTable( array $section ): string
    {
        $columns = array_values( array_filter( (array) ( $section['columns'] ?? [] ), fn ( $column ) => is_scalar( $column ) ) );
        $rows = is_array( $section['rows'] ?? null ) ? $section['rows'] : [];

        if ( empty( $columns ) ) {
            return $this->sectionShell( $section, '<p class="notice">Table sections require a non-empty columns array.</p>' );
        }

        $head = collect( $columns )->map( fn ( $column ) => '<th>' . e( (string) $column ) . '</th>' )->implode( '' );
        $body = collect( $rows )->map( function ( $row ) use ( $columns ) {
            $row = is_array( $row ) ? $row : [];
            $cells = collect( $columns )->map( function ( $column, $index ) use ( $row ) {
                $value = array_key_exists( $column, $row ) ? $row[$column] : ( $row[$index] ?? '' );

                return '<td>' . e( is_scalar( $value ) ? (string) $value : json_encode( $value ) ) . '</td>';
            } )->implode( '' );

            return '<tr>' . $cells . '</tr>';
        } )->implode( '' );

        return $this->sectionShell( $section, '<div class="table-wrap"><table><thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody></table></div>' );
    }

    /** @param array<string, mixed> $section */
    private function renderChartSection( array $section, string $chart ): string
    {
        $items = $this->chartItems( $section['items'] ?? [] );
        $svg = $chart === 'pie' ? $this->pieChart( $items ) : $this->barChart( $items );
        $legend = collect( $items )->map( fn ( $item ) => '<span><i style="background:' . e( $item['color'] ) . '"></i>' . e( $item['label'] ) . '</span>' )->implode( '' );

        return $this->sectionShell( $section, '<div class="chart-block">' . $svg . '<div class="legend">' . $legend . '</div></div>' );
    }

    private function renderUnsupportedSection( string $type ): string
    {
        return '<section class="report-section"><p class="notice">Unsupported section type: ' . e( $type ?: 'missing' ) . '. Supported types are text, kpi_grid, table, bar_chart, pie_chart, and page_break.</p></section>';
    }

    /** @param array<string, mixed> $section */
    private function sectionShell( array $section, string $body ): string
    {
        return '<section class="report-section">' .
            ( ! empty( $section['title'] ) ? '<h3>' . e( (string) $section['title'] ) . '</h3>' : '' ) .
            ( ! empty( $section['description'] ) ? '<p class="section-description">' . e( (string) $section['description'] ) . '</p>' : '' ) .
            $body . '</section>';
    }

    private function chartItems( mixed $items ): array
    {
        $palette = ['#2563eb', '#16a34a', '#f97316', '#dc2626', '#7c3aed', '#0891b2', '#ca8a04', '#be185d'];

        return collect( is_array( $items ) ? $items : [] )->map( function ( $item, $index ) use ( $palette ) {
            $item = is_array( $item ) ? $item : [];

            return [
                'label' => (string) ( $item['label'] ?? 'Item ' . ( $index + 1 ) ),
                'value' => max( 0, (float) ( $item['value'] ?? 0 ) ),
                'color' => (string) ( $item['color'] ?? $palette[$index % count( $palette )] ),
            ];
        } )->filter( fn ( $item ) => $item['value'] > 0 )->values()->all();
    }

    private function barChart( array $items ): string
    {
        if ( empty( $items ) ) {
            return '<p class="notice">Bar charts require at least one positive numeric value.</p>';
        }

        $max = max( array_column( $items, 'value' ) ) ?: 1;
        $bars = collect( $items )->map( function ( $item ) use ( $max ) {
            $width = round( ( $item['value'] / $max ) * 100, 2 );

            return '<div class="bar-row"><span>' . e( $item['label'] ) . '</span><div><b style="width:' . $width . '%;background:' . e( $item['color'] ) . '"></b></div><em>' . e( (string) $item['value'] ) . '</em></div>';
        } )->implode( '' );

        return '<div class="bar-chart">' . $bars . '</div>';
    }

    private function pieChart( array $items ): string
    {
        $total = array_sum( array_column( $items, 'value' ) );

        if ( $total <= 0 ) {
            return '<p class="notice">Pie charts require at least one positive numeric value.</p>';
        }

        $angle = -90.0;
        $paths = '';

        foreach ( $items as $item ) {
            $slice = ( $item['value'] / $total ) * 360;
            $paths .= '<path d="' . $this->pieSlicePath( 100, 100, 82, $angle, $angle + $slice ) . '" fill="' . e( $item['color'] ) . '"></path>';
            $angle += $slice;
        }

        return '<svg class="pie-chart" viewBox="0 0 200 200" role="img">' . $paths . '<circle cx="100" cy="100" r="42" fill="#fff"></circle><text x="100" y="105" text-anchor="middle">' . e( (string) round( $total, 2 ) ) . '</text></svg>';
    }

    private function pieSlicePath( float $cx, float $cy, float $radius, float $startAngle, float $endAngle ): string
    {
        $start = $this->polarToCartesian( $cx, $cy, $radius, $endAngle );
        $end = $this->polarToCartesian( $cx, $cy, $radius, $startAngle );
        $largeArc = ( $endAngle - $startAngle ) <= 180 ? '0' : '1';

        return sprintf( 'M %.4F %.4F A %.4F %.4F 0 %s 0 %.4F %.4F L %.4F %.4F Z', $start[0], $start[1], $radius, $radius, $largeArc, $end[0], $end[1], $cx, $cy );
    }

    private function polarToCartesian( float $cx, float $cy, float $radius, float $angle ): array
    {
        $radians = deg2rad( $angle );

        return [$cx + ( $radius * cos( $radians ) ), $cy + ( $radius * sin( $radians ) )];
    }

    private function optionalParagraph( ?string $value, string $class ): string
    {
        return filled( $value ) ? '<p class="' . e( $class ) . '">' . e( $value ) . '</p>' : '';
    }

    private function metaPill( string $label, string $value ): string
    {
        return '<span><b>' . e( $label ) . '</b>' . e( $value ) . '</span>';
    }

    private function storeDetails(): array
    {
        $storeName = (string) ns()->option->get( 'ns_store_name', 'NexoPOS' );
        $address = (string) ns()->option->get( 'ns_store_address', '' );
        $phone = (string) ns()->option->get( 'ns_store_phone', '' );
        $email = (string) ns()->option->get( 'ns_store_email', '' );
        $contact = collect( [$phone, $email] )->filter()->implode( ' | ' );

        return [
            'name' => $storeName ?: 'NexoPOS',
            'address' => $address ?: 'Store address not configured',
            'contact' => $contact ?: 'Store contact not configured',
        ];
    }

    private function stylesheet(): string
    {
        return <<<'CSS'
:root{font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:#172033;background:#f5f7fb}body{margin:0;background:#f5f7fb}.report-page{max-width:1080px;margin:0 auto;padding:32px}.store-header,.report-footer{display:flex;align-items:center;justify-content:space-between;gap:20px}.store-header{border-bottom:3px solid #172033;padding-bottom:18px}.brand-mark{width:58px;height:58px;border-radius:8px;background:#172033;color:#fff;display:grid;place-items:center;font-size:28px;font-weight:800}.store-copy h1{margin:0;font-size:22px}.store-copy p{margin:3px 0;color:#5d6678}.report-title{padding:26px 0}.eyebrow{text-transform:uppercase;letter-spacing:.08em;color:#64748b;font-size:12px;font-weight:700;margin:0 0 8px}.report-title h2{font-size:34px;line-height:1.1;margin:0}.subtitle{font-size:16px;color:#475569}.meta-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.meta-row span{border:1px solid #dbe2ee;border-radius:8px;background:#fff;padding:8px 10px;color:#475569}.meta-row b{display:block;color:#172033;font-size:11px;text-transform:uppercase}.report-section{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:22px;margin:18px 0;break-inside:avoid}.report-section h3{margin:0 0 8px;font-size:20px}.section-description,.body-copy{color:#475569;line-height:1.6}.notice{border:1px dashed #f59e0b;background:#fffbeb;color:#92400e;border-radius:8px;padding:12px}.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}.kpi-card{border:1px solid #e2e8f0;border-radius:8px;padding:14px}.kpi-card span{color:#64748b;font-size:12px;text-transform:uppercase;font-weight:700}.kpi-card strong{display:block;font-size:28px;margin-top:4px}.kpi-card small{color:#64748b}.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:14px}th,td{text-align:left;border-bottom:1px solid #e2e8f0;padding:10px;vertical-align:top}th{background:#f8fafc;color:#334155}.chart-block{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:18px;align-items:center}.bar-chart{display:grid;gap:10px}.bar-row{display:grid;grid-template-columns:150px 1fr 80px;gap:10px;align-items:center}.bar-row span{color:#334155}.bar-row div{height:16px;background:#e2e8f0;border-radius:999px;overflow:hidden}.bar-row b{height:100%;display:block;border-radius:999px}.bar-row em{font-style:normal;text-align:right;color:#475569}.pie-chart{width:280px;max-width:100%;margin:auto;display:block}.pie-chart text{font-size:17px;font-weight:800;fill:#172033}.legend{display:flex;flex-direction:column;gap:8px}.legend span{display:flex;align-items:center;gap:8px;color:#475569}.legend i{width:12px;height:12px;border-radius:3px;display:inline-block}.report-footer{color:#64748b;border-top:1px solid #e2e8f0;margin-top:28px;padding-top:16px;font-size:12px}.page-break{break-after:page}@media(max-width:760px){.report-page{padding:18px}.chart-block{grid-template-columns:1fr}.bar-row{grid-template-columns:1fr}.bar-row em{text-align:left}}@media print{body{background:#fff}.report-page{max-width:none;padding:0}.report-section{box-shadow:none}.meta-row span,.report-section{border-color:#cbd5e1}}
CSS;
    }
}
