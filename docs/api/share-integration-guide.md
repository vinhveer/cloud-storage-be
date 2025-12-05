# Share API Integration Guide (Frontend)

## Tổng quan tích hợp

Tài liệu này hướng dẫn cách tích hợp Share API vào ứng dụng frontend, bao gồm các pattern và best practices.

## 1. Cấu trúc API Service

### 1.1. Tạo Share Service

```javascript
// services/shareService.js
const API_BASE_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000/api';

class ShareService {
  constructor() {
    this.token = localStorage.getItem('auth_token');
  }

  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const config = {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...(this.token && { Authorization: `Bearer ${this.token}` }),
        ...options.headers,
      },
    };

    if (config.body && typeof config.body === 'object') {
      config.body = JSON.stringify(config.body);
    }

    const response = await fetch(url, config);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error?.message || 'Request failed');
    }

    return data;
  }

  // Tạo share mới
  async createShare({ shareableType, shareableId, userIds, permission }) {
    return this.request('/shares', {
      method: 'POST',
      body: {
        shareable_type: shareableType,
        shareable_id: shareableId,
        user_ids: userIds,
        permission,
      },
    });
  }

  // Danh sách share đã tạo
  async listShares(page = 1, perPage = 15) {
    return this.request(`/shares?page=${page}&per_page=${perPage}`);
  }

  // Chi tiết share
  async getShare(shareId) {
    return this.request(`/shares/${shareId}`);
  }

  // Danh sách share nhận được
  async getReceivedShares(page = 1, perPage = 15) {
    return this.request(`/shares/received?page=${page}&per_page=${perPage}`);
  }

  // Xóa share
  async deleteShare(shareId) {
    return this.request(`/shares/${shareId}`, {
      method: 'DELETE',
    });
  }

  // Thêm user vào share
  async addUsersToShare(shareId, userIds, permission) {
    return this.request(`/shares/${shareId}/users`, {
      method: 'POST',
      body: {
        user_ids: userIds,
        permission,
      },
    });
  }

  // Xóa user khỏi share
  async removeUserFromShare(shareId, userId) {
    return this.request(`/shares/${shareId}/users/${userId}`, {
      method: 'DELETE',
    });
  }
}

export default new ShareService();
```

## 2. React Hooks

### 2.1. Hook quản lý Share

```javascript
// hooks/useShare.js
import { useState, useCallback } from 'react';
import shareService from '../services/shareService';

export const useShare = () => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const createShare = useCallback(async ({ shareableType, shareableId, userIds, permission }) => {
    setLoading(true);
    setError(null);
    try {
      const response = await shareService.createShare({
        shareableType,
        shareableId,
        userIds,
        permission,
      });
      return response.data;
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const deleteShare = useCallback(async (shareId) => {
    setLoading(true);
    setError(null);
    try {
      await shareService.deleteShare(shareId);
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const addUsers = useCallback(async (shareId, userIds, permission) => {
    setLoading(true);
    setError(null);
    try {
      const response = await shareService.addUsersToShare(shareId, userIds, permission);
      return response.added_users || [];
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const removeUser = useCallback(async (shareId, userId) => {
    setLoading(true);
    setError(null);
    try {
      await shareService.removeUserFromShare(shareId, userId);
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  return {
    loading,
    error,
    createShare,
    deleteShare,
    addUsers,
    removeUser,
  };
};
```

### 2.2. Hook danh sách Share

```javascript
// hooks/useShareList.js
import { useState, useEffect, useCallback } from 'react';
import shareService from '../services/shareService';

export const useShareList = (type = 'created', page = 1, perPage = 15) => {
  const [shares, setShares] = useState([]);
  const [pagination, setPagination] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchShares = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = type === 'received'
        ? await shareService.getReceivedShares(page, perPage)
        : await shareService.listShares(page, perPage);

      setShares(response.data?.data || response.data || []);
      setPagination(response.data?.pagination || response.pagination || null);
    } catch (err) {
      setError(err.message);
      setShares([]);
    } finally {
      setLoading(false);
    }
  }, [type, page, perPage]);

  useEffect(() => {
    fetchShares();
  }, [fetchShares]);

  const refresh = useCallback(() => {
    fetchShares();
  }, [fetchShares]);

  return {
    shares,
    pagination,
    loading,
    error,
    refresh,
  };
};
```

## 3. Components

### 3.1. Share Dialog Component

```javascript
// components/ShareDialog.jsx
import { useState } from 'react';
import { useShare } from '../hooks/useShare';

const ShareDialog = ({ shareableType, shareableId, onClose }) => {
  const [selectedUsers, setSelectedUsers] = useState([]);
  const [permission, setPermission] = useState('view');
  const [userSearch, setUserSearch] = useState('');
  const [users, setUsers] = useState([]);
  const { createShare, loading, error } = useShare();

  const handleShare = async () => {
    if (selectedUsers.length === 0) return;

    try {
      const result = await createShare({
        shareableType,
        shareableId,
        userIds: selectedUsers.map(u => u.id),
        permission,
      });

      console.log('Share created:', result);
      onClose();
    } catch (err) {
      console.error('Failed to share:', err);
    }
  };

  return (
    <div className="share-dialog">
      <h2>Share {shareableType}</h2>

      {/* User search and selection */}
      <div className="user-selector">
        <input
          type="text"
          placeholder="Search users..."
          value={userSearch}
          onChange={(e) => setUserSearch(e.target.value)}
        />
        {/* Render user list */}
      </div>

      {/* Permission selector */}
      <div className="permission-selector">
        <label>
          <input
            type="radio"
            value="view"
            checked={permission === 'view'}
            onChange={(e) => setPermission(e.target.value)}
          />
          View only
        </label>
        <label>
          <input
            type="radio"
            value="download"
            checked={permission === 'download'}
            onChange={(e) => setPermission(e.target.value)}
          />
          View & Download
        </label>
        <label>
          <input
            type="radio"
            value="edit"
            checked={permission === 'edit'}
            onChange={(e) => setPermission(e.target.value)}
          />
          Full access
        </label>
      </div>

      {error && <div className="error">{error}</div>}

      <div className="actions">
        <button onClick={onClose}>Cancel</button>
        <button onClick={handleShare} disabled={loading || selectedUsers.length === 0}>
          {loading ? 'Sharing...' : 'Share'}
        </button>
      </div>
    </div>
  );
};

export default ShareDialog;
```

### 3.2. Share List Component

```javascript
// components/ShareList.jsx
import { useShareList } from '../hooks/useShareList';
import { useShare } from '../hooks/useShare';

const ShareList = ({ type = 'created' }) => {
  const [page, setPage] = useState(1);
  const { shares, pagination, loading, error, refresh } = useShareList(type, page);
  const { deleteShare, loading: deleting } = useShare();

  const handleDelete = async (shareId) => {
    if (!confirm('Are you sure you want to revoke this share?')) return;

    try {
      await deleteShare(shareId);
      refresh();
    } catch (err) {
      console.error('Failed to delete share:', err);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;

  return (
    <div className="share-list">
      <h2>{type === 'created' ? 'My Shares' : 'Shared With Me'}</h2>

      {shares.length === 0 ? (
        <p>No shares found</p>
      ) : (
        <>
          <ul>
            {shares.map((share) => (
              <li key={share.share_id}>
                <div>
                  <strong>{share.shareable_name}</strong>
                  <span className="type">{share.shareable_type}</span>
                  {type === 'created' && (
                    <span className="count">{share.shared_with_count} recipients</span>
                  )}
                  {type === 'received' && (
                    <>
                      <span className="owner">by {share.owner.name}</span>
                      <span className="permission">{share.permission}</span>
                    </>
                  )}
                </div>
                {type === 'created' && (
                  <button onClick={() => handleDelete(share.share_id)} disabled={deleting}>
                    Revoke
                  </button>
                )}
              </li>
            ))}
          </ul>

          {pagination && pagination.total_pages > 1 && (
            <div className="pagination">
              <button
                disabled={page === 1}
                onClick={() => setPage(page - 1)}
              >
                Previous
              </button>
              <span>
                Page {pagination.current_page} of {pagination.total_pages}
              </span>
              <button
                disabled={page === pagination.total_pages}
                onClick={() => setPage(page + 1)}
              >
                Next
              </button>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default ShareList;
```

### 3.3. Share Detail Component

```javascript
// components/ShareDetail.jsx
import { useState, useEffect } from 'react';
import shareService from '../services/shareService';
import { useShare } from '../hooks/useShare';

const ShareDetail = ({ shareId, onClose }) => {
  const [share, setShare] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const { removeUser, loading: removing } = useShare();

  useEffect(() => {
    const fetchShare = async () => {
      try {
        const data = await shareService.getShare(shareId);
        setShare(data);
      } catch (err) {
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    fetchShare();
  }, [shareId]);

  const handleRemoveUser = async (userId) => {
    if (!confirm('Remove this user from share?')) return;

    try {
      await removeUser(shareId, userId);
      setShare((prev) => ({
        ...prev,
        shared_with: prev.shared_with.filter(u => u.user_id !== userId),
      }));
    } catch (err) {
      console.error('Failed to remove user:', err);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div>Error: {error}</div>;
  if (!share) return null;

  return (
    <div className="share-detail">
      <h2>{share.shareable_name}</h2>
      <p>Type: {share.shareable_type}</p>
      <p>Created: {new Date(share.created_at).toLocaleString()}</p>
      <p>Shared by: {share.shared_by.name}</p>

      <h3>Recipients ({share.shared_with.length})</h3>
      <ul>
        {share.shared_with.map((user) => (
          <li key={user.user_id}>
            <span>{user.name}</span>
            <span className="permission">{user.permission}</span>
            <button
              onClick={() => handleRemoveUser(user.user_id)}
              disabled={removing}
            >
              Remove
            </button>
          </li>
        ))}
      </ul>

      <button onClick={onClose}>Close</button>
    </div>
  );
};

export default ShareDetail;
```

## 4. Error Handling

### 4.1. Error Handler Utility

```javascript
// utils/errorHandler.js
export const handleShareError = (error) => {
  const message = error.message || 'An error occurred';

  if (message.includes('Unauthenticated')) {
    return 'Please login to continue';
  }

  if (message.includes('not found')) {
    return 'Share or resource not found';
  }

  if (message.includes('Validation failed')) {
    return 'Please check your input';
  }

  if (message.includes('Forbidden')) {
    return 'You do not have permission to perform this action';
  }

  return message;
};
```

## 5. State Management (Redux/Zustand)

### 5.1. Zustand Store Example

```javascript
// stores/shareStore.js
import create from 'zustand';
import shareService from '../services/shareService';

const useShareStore = create((set, get) => ({
  shares: [],
  receivedShares: [],
  loading: false,
  error: null,

  fetchShares: async (page = 1) => {
    set({ loading: true, error: null });
    try {
      const response = await shareService.listShares(page);
      set({ shares: response.data?.data || [], loading: false });
    } catch (error) {
      set({ error: error.message, loading: false });
    }
  },

  fetchReceivedShares: async (page = 1) => {
    set({ loading: true, error: null });
    try {
      const response = await shareService.getReceivedShares(page);
      set({ receivedShares: response.data || [], loading: false });
    } catch (error) {
      set({ error: error.message, loading: false });
    }
  },

  createShare: async ({ shareableType, shareableId, userIds, permission }) => {
    set({ loading: true, error: null });
    try {
      const response = await shareService.createShare({
        shareableType,
        shareableId,
        userIds,
        permission,
      });
      await get().fetchShares();
      return response.data;
    } catch (error) {
      set({ error: error.message, loading: false });
      throw error;
    }
  },

  deleteShare: async (shareId) => {
    set({ loading: true, error: null });
    try {
      await shareService.deleteShare(shareId);
      await get().fetchShares();
    } catch (error) {
      set({ error: error.message, loading: false });
      throw error;
    }
  },
}));

export default useShareStore;
```

## 6. Best Practices

### 6.1. Optimistic Updates

```javascript
const handleRemoveUser = async (shareId, userId) => {
  // Optimistic update
  setShare((prev) => ({
    ...prev,
    shared_with: prev.shared_with.filter(u => u.user_id !== userId),
  }));

  try {
    await shareService.removeUserFromShare(shareId, userId);
  } catch (error) {
    // Rollback on error
    refresh();
    throw error;
  }
};
```

### 6.2. Debounced Search

```javascript
import { useDebounce } from './hooks/useDebounce';

const ShareDialog = () => {
  const [userSearch, setUserSearch] = useState('');
  const debouncedSearch = useDebounce(userSearch, 300);

  useEffect(() => {
    if (debouncedSearch) {
      // Fetch users
    }
  }, [debouncedSearch]);
};
```

### 6.3. Permission Mapping

```javascript
// utils/permissions.js
export const PERMISSION_LABELS = {
  view: 'View only',
  download: 'View & Download',
  edit: 'Full access',
};

export const PERMISSION_ICONS = {
  view: '👁️',
  download: '⬇️',
  edit: '✏️',
};
```

## 7. Testing

### 7.1. Mock Service

```javascript
// __mocks__/shareService.js
export default {
  createShare: jest.fn(),
  listShares: jest.fn(),
  getShare: jest.fn(),
  deleteShare: jest.fn(),
  addUsersToShare: jest.fn(),
  removeUserFromShare: jest.fn(),
};
```

### 7.2. Component Test

```javascript
// ShareDialog.test.jsx
import { render, screen, fireEvent } from '@testing-library/react';
import ShareDialog from './ShareDialog';
import shareService from '../services/shareService';

jest.mock('../services/shareService');

test('creates share successfully', async () => {
  shareService.createShare.mockResolvedValue({
    data: { share_id: 1 },
  });

  render(<ShareDialog shareableType="file" shareableId={1} onClose={jest.fn()} />);

  fireEvent.click(screen.getByText('Share'));
  await waitFor(() => {
    expect(shareService.createShare).toHaveBeenCalled();
  });
});
```

## 8. Integration Checklist

- [ ] Setup API service với authentication
- [ ] Implement error handling
- [ ] Create share dialog component
- [ ] Create share list component (created & received)
- [ ] Create share detail component
- [ ] Implement pagination
- [ ] Add loading states
- [ ] Add optimistic updates
- [ ] Handle edge cases (empty states, errors)
- [ ] Add user search/selection
- [ ] Implement permission selector
- [ ] Add confirmation dialogs
- [ ] Test all endpoints
- [ ] Add proper TypeScript types (if using TS)

