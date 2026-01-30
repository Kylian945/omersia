/**
 * Exemple de test d'intégration plus complexe
 * Ce fichier montre comment tester des composants avec des appels API et du state
 */

import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'

// Exemple de composant qui fait un appel API (à adapter selon votre code)
// Ce n'est qu'un exemple pour montrer les bonnes pratiques

// Mock d'un module API
jest.mock('@/lib/api', () => ({
  fetchProducts: jest.fn(),
}))

describe('Example Integration Test', () => {
  beforeEach(() => {
    // Reset des mocks avant chaque test
    jest.clearAllMocks()
  })

  it('demonstrates API mocking pattern', async () => {
    // Exemple de mock de fonction API
    const mockFetch = jest.fn().mockResolvedValue({
      products: [
        { id: 1, name: 'Product 1', price: 10 },
        { id: 2, name: 'Product 2', price: 20 },
      ],
    })

    // Utiliser le mock dans votre test
    expect(mockFetch).not.toHaveBeenCalled()
  })

  it('demonstrates user interaction testing', async () => {
    const user = userEvent.setup()

    // Simuler un formulaire simple
    const handleSubmit = jest.fn()

    render(
      <form onSubmit={handleSubmit}>
        <input type="text" name="search" placeholder="Search products..." />
        <button type="submit">Search</button>
      </form>
    )

    // Simuler la saisie utilisateur
    const input = screen.getByPlaceholderText('Search products...')
    await user.type(input, 'laptop')

    // Vérifier la valeur
    expect(input).toHaveValue('laptop')

    // Simuler le clic sur le bouton
    const button = screen.getByRole('button', { name: /search/i })
    await user.click(button)

    // Attendre que l'événement soit traité
    await waitFor(() => {
      expect(handleSubmit).toHaveBeenCalledTimes(1)
    })
  })

  it('demonstrates async data loading pattern', async () => {
    // Mock d'une fonction asynchrone
    const mockLoadData = jest.fn().mockResolvedValue({
      data: 'loaded data',
    })

    const result = await mockLoadData()

    expect(result.data).toBe('loaded data')
    expect(mockLoadData).toHaveBeenCalledTimes(1)
  })

  it('demonstrates error handling testing', async () => {
    // Mock d'une fonction qui échoue
    const mockFailingFetch = jest.fn().mockRejectedValue(new Error('API Error'))

    await expect(mockFailingFetch()).rejects.toThrow('API Error')
  })

  it('demonstrates multiple user interactions', async () => {
    const user = userEvent.setup()

    render(
      <div>
        <input type="checkbox" aria-label="Accept terms" />
        <input type="text" placeholder="Email" />
        <select aria-label="Country">
          <option value="">Select country</option>
          <option value="fr">France</option>
          <option value="us">USA</option>
        </select>
        <button>Submit</button>
      </div>
    )

    // Cocher la case
    const checkbox = screen.getByLabelText('Accept terms')
    await user.click(checkbox)
    expect(checkbox).toBeChecked()

    // Saisir un email
    const emailInput = screen.getByPlaceholderText('Email')
    await user.type(emailInput, 'test@example.com')
    expect(emailInput).toHaveValue('test@example.com')

    // Sélectionner un pays
    const select = screen.getByLabelText('Country')
    await user.selectOptions(select, 'fr')
    expect(select).toHaveValue('fr')
  })
})
