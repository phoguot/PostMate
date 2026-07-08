import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { format } from 'date-fns';
import { Observable } from 'rxjs';
import { PageHeader } from '../../../shared/ui/page-header/page-header';
import { PostApiService, PostSavePayload } from '../shared/post-api.service';
import { FanpageApiService } from '../../facebook/shared/facebook-api.service';
import { BrowserProfileApiService } from '../../infra/shared/browser-profile-api.service';
import { Fanpage } from '../../facebook/shared/facebook.model';
import { BrowserProfile } from '../../infra/shared/browser-profile.model';
import { PostContentType, POST_CONTENT_TYPE_META } from '../../../core/models/status.model';
import { NotifyService } from '../../../core/notify/notify.service';
import { ApiError } from '../../../core/http/api.model';

@Component({
  selector: 'app-create-post-page',
  standalone: true,
  imports: [PageHeader, FormsModule],
  templateUrl: './create-post-page.html'
})
export class CreatePostPage implements OnInit {
  private readonly postApi = inject(PostApiService);
  private readonly fanpageApi = inject(FanpageApiService);
  private readonly browserApi = inject(BrowserProfileApiService);
  private readonly notify = inject(NotifyService);
  private readonly router = inject(Router);

  protected readonly PostContentType = PostContentType;
  protected readonly contentTypeOptions = Object.entries(POST_CONTENT_TYPE_META).map(([value, meta]) => ({
    value: Number(value),
    label: meta.label
  }));

  protected readonly fanpages = signal<Fanpage[]>([]);
  protected readonly browsers = signal<BrowserProfile[]>([]);

  protected readonly fanpageId = signal<number | ''>('');
  protected readonly browserProfileId = signal<number | ''>('');
  protected readonly contentType = signal<number>(PostContentType.TEXT);
  protected readonly title = signal('');
  protected readonly content = signal('');
  protected readonly mediaUrlsText = signal('');
  protected readonly scheduleDate = signal(format(new Date(), 'yyyy-MM-dd'));
  protected readonly scheduleTime = signal(format(new Date(), 'HH:mm'));
  protected readonly repeatRule = signal('none');
  protected readonly note = signal('');
  protected readonly autoShortenLink = signal(false);
  protected readonly disableCommentNotif = signal(false);
  protected readonly autoShare = signal(false);
  protected readonly saving = signal(false);

  protected readonly selectedFanpage = computed(() => this.fanpages().find((f) => f.id === this.fanpageId()) ?? null);
  protected readonly contentLength = computed(() => this.content().length);

  ngOnInit(): void {
    this.fanpageApi.list({ pageSize: 100 }).subscribe((res) => {
      this.fanpages.set(res.result);
      if (res.result.length && this.fanpageId() === '') {
        this.fanpageId.set(res.result[0].id);
        this.browserProfileId.set(res.result[0].browserProfile?.id ?? '');
      }
    });
    this.browserApi.list({ pageSize: 100 }).subscribe((res) => this.browsers.set(res.result));
  }

  protected onFanpageChange(id: number | ''): void {
    this.fanpageId.set(id);
    const fanpage = this.fanpages().find((f) => f.id === id);
    if (fanpage?.browserProfile) {
      this.browserProfileId.set(fanpage.browserProfile.id);
    }
  }

  protected saveDraft(): void {
    this.submit((payload) => this.postApi.saveDraft(payload), 'Đã lưu nháp.');
  }

  protected schedulePost(): void {
    if (!this.validate()) {
      return;
    }
    this.submit((payload) => this.postApi.schedule(payload), 'Đã lên lịch đăng bài.');
  }

  protected publishNow(): void {
    if (!this.validate()) {
      return;
    }
    this.submit((payload) => this.postApi.publishNow(payload), 'Đã gửi yêu cầu đăng bài.');
  }

  private validate(): boolean {
    if (this.fanpageId() === '') {
      this.notify.error('Vui lòng chọn fanpage.');
      return false;
    }
    if (!this.content().trim()) {
      this.notify.error('Vui lòng nhập nội dung bài viết.');
      return false;
    }
    return true;
  }

  private submit(action: (payload: PostSavePayload) => Observable<{ ids: number[] }>, successMsg: string): void {
    this.saving.set(true);
    const media = this.mediaUrlsText()
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean)
      .map((url) => ({ url }));

    const payload: PostSavePayload = {
      fanpageId: this.fanpageId() === '' ? undefined : Number(this.fanpageId()),
      browserProfileId: this.browserProfileId() === '' ? undefined : Number(this.browserProfileId()),
      contentType: this.contentType(),
      title: this.title() || undefined,
      content: this.content(),
      media,
      scheduledAt: `${this.scheduleDate()} ${this.scheduleTime()}:00`,
      repeatRule: this.repeatRule(),
      note: this.note() || undefined,
      autoShortenLink: this.autoShortenLink(),
      disableCommentNotif: this.disableCommentNotif(),
      autoShare: this.autoShare()
    };

    action(payload).subscribe({
      next: () => {
        this.saving.set(false);
        this.notify.success(successMsg);
        this.router.navigate(['/posts']);
      },
      error: (err: unknown) => {
        this.saving.set(false);
        this.notify.error(err instanceof ApiError ? err.message : 'Không thể lưu bài viết.');
      }
    });
  }
}
