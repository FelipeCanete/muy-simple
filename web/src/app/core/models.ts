/** Tipos que reflejan las respuestas de la API (api/app/Http/Resources). */

export type DocumentStatus = 'pendiente' | 'firmado_parcial' | 'completo';
export type SignerStatus = 'pendiente' | 'firmado';

export const DOCUMENT_STATUS_LABELS: Record<DocumentStatus, string> = {
  pendiente: 'Pendiente',
  firmado_parcial: 'Firmado parcial',
  completo: 'Completo',
};

export interface User {
  id: number;
  name: string;
  email: string;
}

export interface Signer {
  id: string;
  email: string;
  rut: string;
  status: SignerStatus;
  signed_at: string | null;
  signed_via: 'link' | 'webhook' | null;
  token_expires_at: string;
}

export interface SignDocument {
  id: string;
  title: string;
  original_name: string;
  size: number;
  sha256: string;
  status: DocumentStatus;
  status_label: string;
  signers_total: number | null;
  signers_signed: number | null;
  signers?: Signer[];
  created_at: string;
  updated_at: string;
}

export interface SigningLink {
  signer_id: string;
  email: string;
  url: string;
}

export interface CreatedDocumentResponse {
  data: SignDocument;
  signing_links: SigningLink[];
}

export interface Paginated<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface SigningPage {
  document: {
    title: string;
    original_name: string;
    size: number;
    sha256: string;
    status: DocumentStatus;
    status_label: string;
  };
  signer: {
    email: string;
    status: SignerStatus;
    signed_at: string | null;
    expires_at: string;
  };
}
