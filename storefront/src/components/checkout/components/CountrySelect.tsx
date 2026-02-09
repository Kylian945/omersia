const COUNTRIES = [
  { code: 'FR', name: 'France' },
  { code: 'BE', name: 'Belgique' },
  { code: 'CH', name: 'Suisse' },
  { code: 'DE', name: 'Allemagne' },
  { code: 'IT', name: 'Italie' },
  { code: 'ES', name: 'Espagne' },
  { code: 'PT', name: 'Portugal' },
  { code: 'NL', name: 'Pays-Bas' },
  { code: 'GB', name: 'Royaume-Uni' },
  { code: 'LU', name: 'Luxembourg' },
  { code: 'AT', name: 'Autriche' },
];

type CountrySelectProps = {
  value: string;
  onChange: (value: string) => void;
  className?: string;
};

export function getCountryName(code: string): string {
  const country = COUNTRIES.find(c => c.code === code);
  return country ? country.name : code;
}

export function CountrySelect({ value, onChange, className }: CountrySelectProps) {
  return (
    <select
      value={value}
      onChange={(e) => onChange(e.target.value)}
      className={className}
    >
      {COUNTRIES.map((country) => (
        <option key={country.code} value={country.code}>
          {country.name}
        </option>
      ))}
    </select>
  );
}
