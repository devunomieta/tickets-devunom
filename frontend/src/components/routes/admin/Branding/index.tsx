import {useEffect, useState} from "react";
import {Button, ColorInput, Group, Image, Stack, Text, TextInput} from "@mantine/core";
import {SettingsPage, SettingsSection} from "../../../common/SettingsPage";
import {applyBrandingToDom, brandingClient, PlatformBrandingSettings} from "../../../../api/branding.client.ts";
import {showError, showSuccess} from "../../../../utilites/notifications.tsx";

const AdminBranding = () => {
    const [form, setForm] = useState<PlatformBrandingSettings>({
        app_name: 'CortDevs Tickets',
        primary_color: '#cd58dd',
        logo_url: '',
        dark_logo_url: '',
        favicon_url: '',
    });
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        brandingClient.getBranding()
            .then((data) => {
                setForm({
                    app_name: data.app_name || 'CortDevs Tickets',
                    primary_color: data.primary_color || '#cd58dd',
                    logo_url: data.logo_url || '',
                    dark_logo_url: data.dark_logo_url || '',
                    favicon_url: data.favicon_url || '',
                });
            })
            .catch(() => {});
    }, []);

    const handleSave = async () => {
        setSaving(true);
        try {
            const updated = await brandingClient.updateBranding({
                app_name: form.app_name,
                primary_color: form.primary_color,
                logo_url: form.logo_url || null,
                dark_logo_url: form.dark_logo_url || null,
                favicon_url: form.favicon_url || null,
            });
            applyBrandingToDom(updated);
            showSuccess('Sitewide branding updated.');
        } catch (err: any) {
            showError(err?.response?.data?.message || 'Failed to update sitewide branding.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <SettingsPage
            title="Site Branding"
            description="Manage sitewide app name, primary brand color, logos, and favicon from one place."
        >
            <SettingsSection
                title="Identity & Theme"
                description="Changes apply across the platform immediately after saving."
            >
                <Stack gap="md" p="md">
                    <TextInput
                        label="App Name"
                        placeholder="CortDevs Tickets"
                        value={form.app_name}
                        onChange={(e) => setForm((p) => ({...p, app_name: e.currentTarget.value}))}
                        required
                    />

                    <ColorInput
                        label="Primary Brand Color"
                        placeholder="#cd58dd"
                        value={form.primary_color}
                        onChange={(val) => setForm((p) => ({...p, primary_color: val}))}
                        swatches={['#cd58dd', '#2563eb', '#7c3aed', '#059669', '#dc2626', '#ea580c', '#18181b']}
                    />

                    <TextInput
                        label="Logo URL (Light Background)"
                        description="Paste a public image URL (e.g. from https://media.cortdevs.com/...)"
                        placeholder="https://media.cortdevs.com/logo.png"
                        value={form.logo_url || ''}
                        onChange={(e) => setForm((p) => ({...p, logo_url: e.currentTarget.value}))}
                    />
                    {form.logo_url && (
                        <Group>
                            <Text size="xs" c="dimmed">Preview:</Text>
                            <Image src={form.logo_url} h={36} w="auto" fit="contain" alt="Logo preview"/>
                        </Group>
                    )}

                    <TextInput
                        label="Dark Logo URL (Dark Background, Optional)"
                        placeholder="https://media.cortdevs.com/logo-dark.png"
                        value={form.dark_logo_url || ''}
                        onChange={(e) => setForm((p) => ({...p, dark_logo_url: e.currentTarget.value}))}
                    />

                    <TextInput
                        label="Favicon URL (Optional)"
                        placeholder="https://media.cortdevs.com/favicon.png"
                        value={form.favicon_url || ''}
                        onChange={(e) => setForm((p) => ({...p, favicon_url: e.currentTarget.value}))}
                    />

                    <Group justify="flex-end" mt="sm">
                        <Button loading={saving} onClick={handleSave}>
                            Save Branding
                        </Button>
                    </Group>
                </Stack>
            </SettingsSection>
        </SettingsPage>
    );
};

export default AdminBranding;
