import { isError, getErrorMessage, toError } from '../error-utils'

describe('error-utils', () => {
  describe('isError', () => {
    it('returns true for Error instance', () => {
      const error = new Error('Test error')
      expect(isError(error)).toBe(true)
    })

    it('returns true for custom Error subclasses', () => {
      const typeError = new TypeError('Type error')
      expect(isError(typeError)).toBe(true)
    })

    it('returns false for string', () => {
      expect(isError('error string')).toBe(false)
    })

    it('returns false for object with message property', () => {
      expect(isError({ message: 'error' })).toBe(false)
    })

    it('returns false for null', () => {
      expect(isError(null)).toBe(false)
    })

    it('returns false for undefined', () => {
      expect(isError(undefined)).toBe(false)
    })
  })

  describe('getErrorMessage', () => {
    it('extracts message from Error instance', () => {
      const error = new Error('Test error message')
      expect(getErrorMessage(error)).toBe('Test error message')
    })

    it('returns string directly when error is a string', () => {
      expect(getErrorMessage('Simple error')).toBe('Simple error')
    })

    it('extracts message from object with message property', () => {
      const errorObj = { message: 'Object error' }
      expect(getErrorMessage(errorObj)).toBe('Object error')
    })

    it('returns default message for null', () => {
      expect(getErrorMessage(null)).toBe('Une erreur est survenue')
    })

    it('returns default message for undefined', () => {
      expect(getErrorMessage(undefined)).toBe('Une erreur est survenue')
    })

    it('returns default message for number', () => {
      expect(getErrorMessage(123)).toBe('Une erreur est survenue')
    })

    it('returns default message for boolean', () => {
      expect(getErrorMessage(true)).toBe('Une erreur est survenue')
    })

    it('returns default message for object without message', () => {
      expect(getErrorMessage({})).toBe('Une erreur est survenue')
    })

    it('converts non-string message to string', () => {
      const errorObj = { message: 42 }
      expect(getErrorMessage(errorObj)).toBe('42')
    })
  })

  describe('toError', () => {
    it('returns Error instance as-is', () => {
      const error = new Error('Original error')
      const result = toError(error)
      expect(result).toBe(error)
      expect(result.message).toBe('Original error')
    })

    it('converts string to Error instance', () => {
      const result = toError('String error')
      expect(result).toBeInstanceOf(Error)
      expect(result.message).toBe('String error')
    })

    it('converts object with message to Error', () => {
      const result = toError({ message: 'Object error' })
      expect(result).toBeInstanceOf(Error)
      expect(result.message).toBe('Object error')
    })

    it('converts null to Error with default message', () => {
      const result = toError(null)
      expect(result).toBeInstanceOf(Error)
      expect(result.message).toBe('Une erreur est survenue')
    })

    it('converts undefined to Error with default message', () => {
      const result = toError(undefined)
      expect(result).toBeInstanceOf(Error)
      expect(result.message).toBe('Une erreur est survenue')
    })

    it('preserves Error subclass types', () => {
      const typeError = new TypeError('Type error')
      const result = toError(typeError)
      expect(result).toBe(typeError)
      expect(result).toBeInstanceOf(TypeError)
    })
  })
})
