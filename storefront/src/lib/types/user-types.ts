/**
 * Types pour l'utilisateur et l'authentification
 */

export type AuthUser = {
  id: number;
  firstname?: string | null;
  lastname?: string | null;
  email: string;
};

export type UserProfile = {
  id: number;
  firstname: string;
  lastname: string;
  email: string;
};

export type MeResponse = {
  user?: AuthUser;
};

export type ProfilePayload = {
  firstname: string;
  lastname: string;
  email: string;
};
