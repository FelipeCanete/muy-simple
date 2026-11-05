import { Component, computed, input } from '@angular/core';
import { DOCUMENT_STATUS_LABELS, DocumentStatus } from '../core/models';

@Component({
  selector: 'app-status-badge',
  template: `<span class="badge" [class]="'badge badge--' + status()">{{ label() }}</span>`,
})
export class StatusBadge {
  readonly status = input.required<DocumentStatus>();
  readonly label = computed(() => DOCUMENT_STATUS_LABELS[this.status()] ?? this.status());
}
