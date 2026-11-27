import {useEffect, useState} from "react";
import {
    Badge,
    Button,
    Code,
    CopyButton,
    Divider,
    Group,
    PasswordInput,
    SegmentedControl,
    Stack,
    Switch,
    Text,
    TextInput,
} from "@mantine/core";
import {
    AccountPaymentGatewaySettings,
    externalPaymentClient,
    GatewayMode,
    GatewayProvider,
} from "../../../../api/externalPayment.client.ts";
import {SettingsSection} from "../../../common/SettingsPage";
import {showError, showSuccess} from "../../../../utilites/notifications.tsx";
import {getConfig} from "../../../../utilites/config.ts";
import {IdParam} from "../../../../types.ts";

interface Props {
    accountId?: IdParam;
    isReadOnly: boolean;
}

interface GatewayFormState {
    is_enabled: boolean;
    mode: GatewayMode;
    test_public_key: string;
    test_secret_key: string;
    test_webhook_secret: string;
    live_public_key: string;
    live_secret_key: string;
    live_webhook_secret: string;
}

const emptyForm = (): GatewayFormState => ({
    is_enabled: false,
    mode: 'test',
    test_public_key: '',
    test_secret_key: '',
    test_webhook_secret: '',
    live_public_key: '',
    live_secret_key: '',
    live_webhook_secret: '',
});

const toFormState = (s?: AccountPaymentGatewaySettings): GatewayFormState => ({
    is_enabled: s?.is_enabled ?? false,
    mode: s?.mode ?? 'test',
    test_public_key: s?.test_public_key ?? '',
    test_secret_key: '',
    test_webhook_secret: '',
    live_public_key: s?.live_public_key ?? '',
    live_secret_key: '',
    live_webhook_secret: '',
});

export const PaymentGatewaysSection = ({accountId, isReadOnly}: Props) => {
    const [saved, setSaved] = useState<Record<GatewayProvider, AccountPaymentGatewaySettings> | null>(null);
    const [forms, setForms] = useState<Record<GatewayProvider, GatewayFormState>>({
        paystack: emptyForm(),
        bachs: emptyForm(),
    });
    const [saving, setSaving] = useState<GatewayProvider | null>(null);

    const apiBase = String(getConfig('VITE_API_URL_CLIENT', '')).replace(/\/$/, '');

    useEffect(() => {
        if (!accountId) return;
        externalPaymentClient.getAccountGateways(accountId)
            .then((data) => {
                setSaved(data);
                setForms({
                    paystack: toFormState(data.paystack),
                    bachs: toFormState(data.bachs),
                });
            })
            .catch(() => {
                // Silently ignore if migration hasn't run yet
            });
    }, [accountId]);

    const updateField = <K extends keyof GatewayFormState>(
        provider: GatewayProvider,
        field: K,
        value: GatewayFormState[K]
    ) => {
        setForms((prev) => ({
            ...prev,
            [provider]: {...prev[provider], [field]: value},
        }));
    };

    const handleSave = async (provider: GatewayProvider) => {
        if (!accountId) return;
        setSaving(provider);
        try {
            const f = forms[provider];
            const updated = await externalPaymentClient.updateAccountGateway(accountId, provider, {
                is_enabled: f.is_enabled,
                mode: f.mode,
                test_public_key: f.test_public_key,
                test_secret_key: f.test_secret_key || undefined,
                test_webhook_secret: f.test_webhook_secret || undefined,
                live_public_key: f.live_public_key,
                live_secret_key: f.live_secret_key || undefined,
                live_webhook_secret: f.live_webhook_secret || undefined,
            });
            setSaved((prev) => ({...(prev || {} as any), [provider]: updated}));
            setForms((prev) => ({...prev, [provider]: toFormState(updated)}));
            showSuccess(`${provider === 'paystack' ? 'Paystack' : 'Bachs.io'} settings saved.`);
        } catch (err: any) {
            showError(err?.response?.data?.message || 'Failed to save payment gateway settings.');
        } finally {
            setSaving(null);
        }
    };

    const renderGatewayCard = (
        provider: GatewayProvider,
        title: string,
        subtitle: string,
        showPublicKey: boolean,
        showWebhookSecret: boolean,
    ) => {
        const f = forms[provider];
        const s = saved?.[provider];
        const webhookUrl = `${apiBase}/public/webhooks/${provider}`;

        return (
            <Stack gap="md" p="md" style={{border: '1px solid var(--mantine-color-gray-3)', borderRadius: 8}}>
                <Group justify="space-between" align="center">
                    <div>
                        <Group gap="xs">
                            <Text fw={600} size="md">{title}</Text>
                            <Badge color={f.is_enabled ? 'green' : 'gray'} variant="light">
                                {f.is_enabled ? 'Enabled' : 'Disabled'}
                            </Badge>
                            <Badge color={f.mode === 'live' ? 'blue' : 'orange'} variant="outline">
                                {f.mode === 'live' ? 'Live Mode' : 'Test / Sandbox Mode'}
                            </Badge>
                        </Group>
                        <Text size="xs" c="dimmed">{subtitle}</Text>
                    </div>
                    <Switch
                        label="Enable"
                        checked={f.is_enabled}
                        disabled={isReadOnly}
                        onChange={(e) => updateField(provider, 'is_enabled', e.currentTarget.checked)}
                    />
                </Group>

                <Group justify="space-between" align="center">
                    <Text size="sm" fw={500}>Active Environment</Text>
                    <SegmentedControl
                        size="xs"
                        disabled={isReadOnly}
                        value={f.mode}
                        onChange={(val) => updateField(provider, 'mode', val as GatewayMode)}
                        data={[
                            {label: 'Test / Sandbox', value: 'test'},
                            {label: 'Live', value: 'live'},
                        ]}
                    />
                </Group>

                <Divider label="Test / Sandbox Keys" labelPosition="left"/>
                {showPublicKey && (
                    <TextInput
                        label="Test Public Key"
                        placeholder="pk_test_..."
                        disabled={isReadOnly}
                        value={f.test_public_key}
                        onChange={(e) => updateField(provider, 'test_public_key', e.currentTarget.value)}
                    />
                )}
                <PasswordInput
                    label={
                        <Group gap={6}>
                            <span>Test Secret Key</span>
                            {s?.has_test_secret_key && <Badge size="xs" color="green">Saved</Badge>}
                        </Group>
                    }
                    placeholder={s?.has_test_secret_key ? 'Leave blank to keep existing key' : 'sk_test_... / sk_sandbox_...'}
                    disabled={isReadOnly}
                    value={f.test_secret_key}
                    onChange={(e) => updateField(provider, 'test_secret_key', e.currentTarget.value)}
                />
                {showWebhookSecret && (
                    <PasswordInput
                        label={
                            <Group gap={6}>
                                <span>Test Webhook Secret</span>
                                {s?.has_test_webhook_secret && <Badge size="xs" color="green">Saved</Badge>}
                            </Group>
                        }
                        placeholder={s?.has_test_webhook_secret ? 'Leave blank to keep existing secret' : 'whsec_...'}
                        disabled={isReadOnly}
                        value={f.test_webhook_secret}
                        onChange={(e) => updateField(provider, 'test_webhook_secret', e.currentTarget.value)}
                    />
                )}

                <Divider label="Live Keys" labelPosition="left"/>
                {showPublicKey && (
                    <TextInput
                        label="Live Public Key"
                        placeholder="pk_live_..."
                        disabled={isReadOnly}
                        value={f.live_public_key}
                        onChange={(e) => updateField(provider, 'live_public_key', e.currentTarget.value)}
                    />
                )}
                <PasswordInput
                    label={
                        <Group gap={6}>
                            <span>Live Secret Key</span>
                            {s?.has_live_secret_key && <Badge size="xs" color="green">Saved</Badge>}
                        </Group>
                    }
                    placeholder={s?.has_live_secret_key ? 'Leave blank to keep existing key' : 'sk_live_...'}
                    disabled={isReadOnly}
                    value={f.live_secret_key}
                    onChange={(e) => updateField(provider, 'live_secret_key', e.currentTarget.value)}
                />
                {showWebhookSecret && (
                    <PasswordInput
                        label={
                            <Group gap={6}>
                                <span>Live Webhook Secret</span>
                                {s?.has_live_webhook_secret && <Badge size="xs" color="green">Saved</Badge>}
                            </Group>
                        }
                        placeholder={s?.has_live_webhook_secret ? 'Leave blank to keep existing secret' : 'whsec_...'}
                        disabled={isReadOnly}
                        value={f.live_webhook_secret}
                        onChange={(e) => updateField(provider, 'live_webhook_secret', e.currentTarget.value)}
                    />
                )}

                <Group justify="space-between" align="center" wrap="wrap">
                    <Group gap="xs">
                        <Text size="xs" c="dimmed">Webhook URL:</Text>
                        <Code>{webhookUrl}</Code>
                        <CopyButton value={webhookUrl}>
                            {({copied, copy}) => (
                                <Button size="compact-xs" variant="subtle" onClick={copy}>
                                    {copied ? 'Copied' : 'Copy'}
                                </Button>
                            )}
                        </CopyButton>
                    </Group>
                    <Button
                        size="sm"
                        loading={saving === provider}
                        disabled={isReadOnly}
                        onClick={() => handleSave(provider)}
                    >
                        Save {title}
                    </Button>
                </Group>
            </Stack>
        );
    };

    return (
        <SettingsSection
            title="Payment Gateways (Paystack & Bachs.io)"
            description="Enable or disable gateways, store Test and Live keys securely (encrypted), and switch modes anytime without re-entering keys."
        >
            <Stack gap="lg" p="md">
                {renderGatewayCard(
                    'paystack',
                    'Paystack',
                    'Accept cards, bank transfers, USSD, and mobile money via Paystack.',
                    true,
                    false,
                )}
                {renderGatewayCard(
                    'bachs',
                    'Bachs.io',
                    'Accept multi-currency cards, bank transfers, mobile money, and stablecoins via Bachs.io.',
                    false,
                    true,
                )}
            </Stack>
        </SettingsSection>
    );
};
