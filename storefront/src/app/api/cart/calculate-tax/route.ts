import { NextRequest, NextResponse } from 'next/server';
import { apiJson } from '@/lib/api-http';

// Map country names to ISO codes
const countryNameToCode: Record<string, string> = {
  'France': 'FR',
  'france': 'FR',
  'Suisse': 'CH',
  'suisse': 'CH',
  'Belgique': 'BE',
  'belgique': 'BE',
  'Allemagne': 'DE',
  'allemagne': 'DE',
  'Italie': 'IT',
  'italie': 'IT',
  'Espagne': 'ES',
  'espagne': 'ES',
  'Portugal': 'PT',
  'portugal': 'PT',
  'Pays-Bas': 'NL',
  'pays-bas': 'NL',
  'Luxembourg': 'LU',
  'luxembourg': 'LU',
};

function getCountryCode(countryNameOrCode: string): string {
  // If already a 2-letter code, return as-is
  if (countryNameOrCode.length === 2) {
    return countryNameOrCode.toUpperCase();
  }

  // Try to find in mapping
  return countryNameToCode[countryNameOrCode] || countryNameOrCode;
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();

    const { total, address } = body;

    if (!address || !address.country) {
      return NextResponse.json(
        { error: 'Address with country is required' },
        { status: 400 }
      );
    }

    // Convert country name to ISO code
    const countryCode = getCountryCode(address.country);

    // Call backend to calculate included tax (prix TTC -> extract tax amount)
    const { data, res } = await apiJson<{
      tax_total: number;
      tax_rate: number;
      price_excluding_tax: number;
    }>('/calculate-included-tax', {
      method: 'POST',
      body: {
        price_including_tax: total || 0,
        address: {
          country: countryCode,
          state: address.state || null,
          postal_code: address.postcode || address.postal_code || null,
        },
      },
    });

    if (!res.ok || !data) {
      console.error('Backend tax calculation error');

      // Return 0 tax if calculation fails
      return NextResponse.json({
        tax_total: 0,
        tax_rate: 0,
      });
    }

    return NextResponse.json({
      tax_total: data.tax_total || 0,
      tax_rate: data.tax_rate || 0,
    });
  } catch (error) {
    console.error('Error calculating tax:', error);

    // Return 0 tax on error
    return NextResponse.json({
      tax_total: 0,
      tax_rate: 0,
    });
  }
}
