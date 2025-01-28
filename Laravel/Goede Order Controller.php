<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Pdf\Pdf;
use Illuminate\Support\Facades\Http;

class SaveOrderController extends Controller
{
    /**
     * Verwerkt het ontvangen order en verstuurt het naar een externe API.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveOrder(Request $request)
    {
        try {
            // De gegevens van het verzoek ophalen
            $orderData = $request->all();

            // Validatie van de gegevens
            $validatedData = $request->validate([
                'products' => 'required|array',
                'products.*.ProductNaam' => 'required|string',
                'products.*.Prijs' => 'required|numeric',
                'products.*.Aantal' => 'required|integer',
            ]);

            // Bereken het totaal
            $total = 0;
            foreach ($orderData['products'] as $product) {
                $total += $product['Subtotaal'];
            }

            // Maak een array voor de data die naar de externe API wordt gestuurd
            $dataToSend = [
                'total' => $total,
                'products' => $orderData['products'],
            ];

            // Verstuur de order naar de externe API (Webhook)
            $response = Http::post('https://webhook.site/b9ddb148-1873-42d9-8477-9b0d56fa9e18', $dataToSend);

            // Controleer of de aanvraag succesvol was
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Bestelling succesvol verzonden naar externe API.',
                    'data' => $response->json(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Fout bij het versturen van de bestelling naar de externe service.',
                    'error' => $response->body(),
                ], 500);
            }

        } catch (\Exception $e) {
            // Toon foutmelding en stacktrace in de response voor debugging
            return response()->json([
                'success' => false,
                'message' => 'Er is een probleem opgetreden bij het verwerken van de bestelling.',
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}

// Hieronder is het met de database functie

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderLine;

class SaveOrderController extends Controller
{
    /**
     * Verwerkt het ontvangen order en slaat het op in de database.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveOrder(Request $request)
    {
        try {
            // Validatie van de gegevens
            $validatedData = $request->validate([
                'products' => 'required|array',
                'products.*.ProductNaam' => 'required|string',
                'products.*.Prijs' => 'required|numeric',
                'products.*.Aantal' => 'required|integer',
            ]);

            // Bereken het totaal
            $total = 0;
            foreach ($request->products as $product) {
                $total += $product['Prijs'] * $product['Aantal'];
            }

            // Maak een nieuwe order aan en sla het op in de database
            $order = Order::create([
                'total' => $total,
            ]);

            // Voeg de producten (orderlines) toe aan de order
            foreach ($request->products as $product) {
                OrderLine::create([
                    'order_id' => $order->id,
                    'product_name' => $product['ProductNaam'],
                    'price' => $product['Prijs'],
                    'quantity' => $product['Aantal'],
                    'subtotal' => $product['Prijs'] * $product['Aantal'],
                ]);
            }

            // Succesrespons retourneren
            return response()->json([
                'success' => true,
                'message' => 'Bestelling succesvol opgeslagen!',
                'order_id' => $order->id,
            ]);

        } catch (\Exception $e) {
            // Toon foutmelding en stacktrace in de response voor debugging
            return response()->json([
                'success' => false,
                'message' => 'Er is een probleem opgetreden bij het opslaan van de bestelling.',
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}
