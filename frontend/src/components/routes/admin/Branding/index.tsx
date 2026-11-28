import {useEffect, useState} from "react";
import {
    Badge,
    Button,
    ColorInput,
    FileInput,
    Group,
    Image,
    SegmentedControl,
    Stack,
    Text,
    TextInput,
} from "@mantine/core";
import {SettingsPage, SettingsSection} from "../../../common/SettingsPage";
import {
    applyBrandingToDom,
    brandingClient,
    PlatformBrandingSettings,
    ThemeMode,
} from "../../../../api/branding.client.ts";
import {showError, showSuccess} from "../../../../utilites/notifications.tsx";

const AdminBranding = () => {
    const [form, setForm] = useState<PlatformBrandingSettings>({
        app_name: 'CortDevs Tickets',
        primary_color: '#8b5cf6',
        theme_mode: 'dark',
        accent_color: '#10b981',
        logo_url: '',
        dark_logo_url: '',
        favicon_url: '',
    });
    const [saving, setSaving] = useState(false);
    const [uploading, setUploading] = useState<string | null>(null);

    useEffect(() => {
        brandingClient.getBranding()
            .then((data) => {
                setForm({
                    app_name: data.app_name || 'CortDevs Tickets',
                    primary_color: data.primary_color || '#8b5cf6',
                    theme_mode: data.theme_mode || 'dark',
                    accent_color: data.accent_color || '#10b981',
                    logo_url: data.logo_url || '',
                    dark_logo_url: data.dark_logo_url || '',
                    favicon_url: data.favicon_url || '',
                });
            })
            .catch(() => {});
    }, []);

    const handleUpload = async (type: 'logo' | 'dark_logo' | 'favicon', file: File | null) => {
        if (!file) return;
        setUploading(type);
        try {
            const res = await brandingClient.uploadAsset(type, file);
            setForm((p) => ({
                ...p,
                logo_url: res.branding.logo_url || '',
                dark_logo_url: res.branding.dark_logo_url || '',
                favicon_url: res.branding.favicon_url || '',
            }));
            applyBrandingToDom(res.branding);
            showSuccess(`Uploaded to Cloudflare R2 (${res.disk}): ${res.url}`);
        } catch (err: any) {
            showError(err?.response?.data?.message || 'Failed to upload asset to Cloudflare R2.');
        } finally {
            setUploading(null);
        }
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            const updated = await brandingClient.updateBranding({
                app_name: form.app_name,
                primary_color: form.primary_color,
                theme_mode: form.theme_mode,
                accent_color: form.accent_color,
                logo_url: form.logo_url || null,
                dark_logo_url: form.dark_logo_url || null,
                favicon_url: form.favicon_url || null,
            });
            applyBrandingToDom(updated);
            showSuccess('Sitewide branding & theme updated.');
        } catch (err: any) {
            showError(err?.response?.data?.message || 'Failed to update sitewide branding.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <SettingsPage
            title="Site Branding"
            description="Manage sitewide theme (Dark/Light), primary & accent colors, and upload logos/favicons directly to Cloudflare R2."
        >
            <SettingsSection
                title="Theme & Colors"
                description="Applies sitewide immediately after saving."
            >
                <Stack gap="md" p="md">
                    <TextInput
                        label="App Name"
                        placeholder="CortDevs Tickets"
                        value={form.app_name}
                        onChange={(e) => setForm((p) => ({...p, app_name: e.currentTarget.value}))}
                        required
                    />

                    <Group justify="space-between" align="center">
                        <div>
                            <Text size="sm" fw={500}>Sitewide Theme Mode</Text>
                            <Text size="xs" c="dimmed">Switch between Dark and Light layout across the platform.</Text>
                        </div>
                        <SegmentedControl
                            value={form.theme_mode}
                            onChange={(val) => setForm((p) => ({...p, theme_mode: val as ThemeMode}))}
                            data={[
                                {label: 'Dark Mode', value: 'dark'},
                                {label: 'Light Mode', value: 'light'},
                            ]}
                        />
                    </Group>

                    <Group grow>
                        <ColorInput
                            label="Primary Brand Color"
                            placeholder="#8b5cf6"
                            value={form.primary_color}
                            onChange={(val) => setForm((p) => ({...p, primary_color: val}))}
                            swatches={['#8b5cf6', '#cd58dd', '#3b82f6', '#10b981', '#f59e0b', '#ef4444']}
                        />
                        <ColorInput
                            label="Accent Color"
                            placeholder="#10b981"
                            value={form.accent_color}
                            onChange={(val) => setForm((p) => ({...p, accent_color: val}))}
                            swatches={['#10b981', '#06b6d4', '#f43f5e', '#f59e0b', '#8b5cf6', '#3b82f6']}
                        />
                    </Group>
                </Stack>
            </SettingsSection>

            <SettingsSection
                title="Logos & Favicon (Cloudflare R2)"
                description="Uploaded files are stored in your Cloudflare R2 bucket and served via media.cortdevs.com."
            >
                <Stack gap="md" p="md">
                    <Group align="flex-end" grow>
                        <FileInput
                            label="Upload Light Logo (PNG, SVG, WEBP)"
                            placeholder="Choose logo file..."
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            disabled={uploading === 'logo'}
                            onChange={(file) => handleUpload('logo', file)}
                        />
                        <TextInput
                            label="Light Logo URL"
                            value={form.logo_url || ''}
                            onChange={(e) => setForm((p) => ({...p, logo_url: e.currentTarget.value}))}
                        />
                    </Group>
                    {form.logo_url && (
                        <Group gap="xs">
                            <Image src={form.logo_url} h={36} w="auto" fit="contain" alt="Light logo"/>
                            <Badge color="teal" variant="light">Cloudflare R2</Badge>
                        </Group>
                    )}

                    <Group align="flex-end" grow>
                        <FileInput
                            label="Upload Dark Logo (PNG, SVG, WEBP)"
                            placeholder="Choose dark logo file..."
                            accept="image/png,image/jpeg,image/svg+xml,image/webp"
                            disabled={uploading === 'dark_logo'}
                            onChange={(file) => handleUpload('dark_logo', file)}
                        />
                        <TextInput
                            label="Dark Logo URL"
                            value={form.dark_logo_url || ''}
                            onChange={(e) => setForm((p) => ({...p, dark_logo_url: e.currentTarget.value}))}
                        />
                    </Group>
                    {form.dark_logo_url && (
                        <Group gap="xs">
                            <Image src={form.dark_logo_url} h={36} w="auto" fit="contain" alt="Dark logo"/>
                            <Badge color="teal" variant="light">Cloudflare R2</Badge>
                        </Group>
                    )}

                    <Group align="flex-end" grow>
                        <FileInput
                            label="Upload Favicon (PNG, SVG, ICO)"
                            placeholder="Choose favicon file..."
                            accept="image/png,image/svg+xml,image/x-icon,image/webp"
                            disabled={uploading === 'favicon'}
                            onChange={(file) => handleUpload('favicon', file)}
                        />
                        <TextInput
                            label="Favicon URL"
                            value={form.favicon_url || ''}
                            onChange={(e) => setForm((p) => ({...p, favicon_url: e.currentTarget.value}))}
                        />
                    </Group>
                    {form.favicon_url && (
                        <Group gap="xs">
                            <Image src={form.favicon_url} h={24} w={24} fit="contain" alt="Favicon"/>
                            <Badge color="teal" variant="light">Cloudflare R2</Badge>
                        </Group>
                    )}

                    <Group justify="flex-end" mt="sm">
                        <Button loading={saving} onClick={handleSave}>
                            Save Branding & Theme
                        </Button>
                    </Group>
                </Stack>
            </SettingsSection>
        </SettingsPage>
    );
};

export default AdminBranding;
